<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\FmArquivo;
use App\Models\FmEmpresaAcesso;
use App\Models\FmGrupo;
use App\Models\FmPasta;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class FileManagerController extends Controller
{
    // ─────────────────────────────────────────────────────────────
    // ACESSO
    // ─────────────────────────────────────────────────────────────

    /**
     * Resolve a company_id ativa para a requisição.
     * Master admin pode escolher via query param; usuário normal usa a sua.
     */
    private function resolveCompanyId(Request $request): int
    {
        $user = $request->user();

        if ($user->is_master_admin) {
            $companyId = $request->get('company_id') ?? session('fm_company_id');
            if (!$companyId) {
                $companyId = Company::orderBy('id')->value('id');
            }
            session(['fm_company_id' => $companyId]);
            return (int) $companyId;
        }

        // Usuário normal: verifica acesso liberado
        if (!$user->company_id) {
            abort(403, 'Você não tem empresa vinculada.');
        }

        $hasAccess = DB::table('fm_empresa_acesso')->where('company_id', $user->company_id)->exists();
        if (!$hasAccess) {
            abort(403, 'Sua empresa não tem acesso ao gerenciador de arquivos. Solicite ao Administrador Master.');
        }

        return (int) $user->company_id;
    }

    /**
     * Verifica se o usuário pode realizar uma ação em uma pasta.
     */
    private function canInFolder(User $user, FmPasta $pasta, string $perm = 'visualizar'): bool
    {
        if ($user->is_master_admin) return true;
        if ($pasta->is_root)        return true; // pasta raiz: acesso livre

        $column = 'pode_' . $perm;

        $userGroupIds = DB::table('fm_grupo_users')
            ->where('user_id', $user->id)
            ->pluck('grupo_id');

        return DB::table('fm_pasta_grupos')
            ->where('pasta_id', $pasta->id)
            ->whereIn('grupo_id', $userGroupIds)
            ->where($column, true)
            ->exists();
    }

    private function requireFileManagerAdmin(User $user): void
    {
        if (!$user->is_master_admin && !$user->hasRole('Administrador')) {
            abort(403, 'Apenas administradores podem gerenciar grupos e permissões.');
        }
    }

    private function requireFilePermission(User $user, FmArquivo $arquivo, string $permission): void
    {
        $pasta = FmPasta::withTrashed()->find($arquivo->pasta_id);

        if (!$pasta || !$this->canInFolder($user, $pasta, $permission)) {
            abort(403, 'Você não tem permissão para realizar esta ação.');
        }
    }

    // ─────────────────────────────────────────────────────────────
    // ROTAS PRINCIPAIS
    // ─────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $user      = $request->user();
        $companyId = $this->resolveCompanyId($request);
        $company   = Company::findOrFail($companyId);

        // Garante que a pasta raiz existe
        $root = FmPasta::firstOrCreate(
            ['company_id' => $companyId, 'is_root' => true],
            ['nome' => $company->nome_fantasia ?? 'Minha Empresa', 'created_by' => $user->id]
        );

        $pastaId = $request->get('pasta_id');
        $secao   = $request->get('secao', 'drive'); // drive | favoritos | lixeira

        // Pasta atual
        $pastaAtual = $pastaId
            ? FmPasta::where('company_id', $companyId)->findOrFail($pastaId)
            : $root;

        // Verificação de permissão
        if (!$this->canInFolder($user, $pastaAtual, 'visualizar')) {
            abort(403, 'Você não tem permissão para visualizar esta pasta.');
        }

        // Breadcrumb
        $breadcrumb = $this->buildBreadcrumb($pastaAtual);

        // Subpastas e arquivos da pasta atual
        [$subpastas, $arquivos] = $this->loadFolderContents($companyId, $pastaAtual, $user, $secao);

        // Árvore de pastas (para o painel esquerdo)
        $tree = $this->buildTree($companyId, $root->id, $user);

        // Storage usage
        $storageUsed = FmArquivo::where('company_id', $companyId)->sum('tamanho');

        // Empresas (para master_admin escolher)
        $companies = $user->is_master_admin
            ? Company::select('id', 'nome_fantasia')->orderBy('nome_fantasia')->get()
            : null;

        return Inertia::render('FileManager/Index', [
            'company'      => $company,
            'companies'    => $companies,
            'root'         => $root,
            'pastaAtual'   => $pastaAtual,
            'subpastas'    => $subpastas,
            'arquivos'     => $arquivos,
            'breadcrumb'   => $breadcrumb,
            'tree'         => $tree,
            'storageUsed'  => $storageUsed,
            'secao'        => $secao,
            'canUpload'    => $this->canInFolder($user, $pastaAtual, 'incluir'),
            'canDelete'    => $this->canInFolder($user, $pastaAtual, 'excluir'),
        ]);
    }

    private function loadFolderContents(int $companyId, FmPasta $pasta, User $user, string $secao): array
    {
        if ($secao === 'favoritos') {
            $arquivos = FmArquivo::where('company_id', $companyId)
                ->where('is_starred', true)
                ->with(['createdBy:id,name', 'pasta'])
                ->orderBy('nome_original')
                ->get()
                ->filter(fn (FmArquivo $arquivo) => $arquivo->pasta && $this->canInFolder($user, $arquivo->pasta, 'visualizar'))
                ->values();

            return [
                collect(),
                $arquivos,
            ];
        }

        if ($secao === 'lixeira') {
            $pastas = FmPasta::onlyTrashed()
                ->where('company_id', $companyId)
                ->orderBy('nome')
                ->get()
                ->filter(fn (FmPasta $item) => $this->canInFolder($user, $item, 'visualizar'))
                ->values();
            $arquivos = FmArquivo::onlyTrashed()
                ->where('company_id', $companyId)
                ->with('createdBy:id,name')
                ->orderByDesc('deleted_at')
                ->get()
                ->filter(function (FmArquivo $arquivo) use ($user) {
                    $pasta = FmPasta::withTrashed()->find($arquivo->pasta_id);
                    return $pasta && $this->canInFolder($user, $pasta, 'visualizar');
                })
                ->values();

            return [
                $pastas,
                $arquivos,
            ];
        }

        $subpastas = FmPasta::where('company_id', $companyId)
            ->where('parent_id', $pasta->id)
            ->where('is_root', false)
            ->orderBy('nome')
            ->get();

        $arquivos = FmArquivo::where('company_id', $companyId)
            ->where('pasta_id', $pasta->id)
            ->with('createdBy:id,name')
            ->orderBy('nome_original')
            ->get();

        return [$subpastas, $arquivos];
    }

    private function buildBreadcrumb(FmPasta $pasta): array
    {
        $crumbs = [];
        $current = $pasta;

        while ($current) {
            array_unshift($crumbs, ['id' => $current->id, 'nome' => $current->nome, 'is_root' => $current->is_root]);
            $current = $current->parent_id ? FmPasta::find($current->parent_id) : null;
        }

        return $crumbs;
    }

    private function buildTree(int $companyId, int $rootId, User $user): array
    {
        $allFolders = FmPasta::where('company_id', $companyId)
            ->orderBy('nome')
            ->get()
            ->keyBy('id');

        return $this->buildTreeRecursive($allFolders, $rootId, $user);
    }

    private function buildTreeRecursive($allFolders, int $parentId, User $user): array
    {
        return $allFolders->where('parent_id', $parentId)
            ->where('is_root', false)
            ->filter(fn (FmPasta $pasta) => $this->canInFolder($user, $pasta, 'visualizar'))
            ->map(function ($pasta) use ($allFolders, $user) {
                return [
                    'id'       => $pasta->id,
                    'nome'     => $pasta->nome,
                    'children' => $this->buildTreeRecursive($allFolders, $pasta->id, $user),
                ];
            })->values()->toArray();
    }

    // ─────────────────────────────────────────────────────────────
    // UPLOAD
    // ─────────────────────────────────────────────────────────────

    public function upload(Request $request)
    {
        $user      = $request->user();
        $companyId = $this->resolveCompanyId($request);

        $request->validate([
            'file'     => [
                'required',
                'file',
                'max:51200', // 50MB
                'mimes:pdf,doc,docx,xls,xlsx,csv,txt,png,jpg,jpeg,webp',
                'mimetypes:application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv,text/plain,image/png,image/jpeg,image/webp',
            ],
            'pasta_id' => 'nullable|integer',
        ]);

        $pastaId = $request->input('pasta_id');
        $pasta   = $pastaId
            ? FmPasta::where('company_id', $companyId)->findOrFail($pastaId)
            : FmPasta::where('company_id', $companyId)->where('is_root', true)->firstOrFail();

        if (!$this->canInFolder($user, $pasta, 'incluir')) {
            abort(403, 'Você não tem permissão para fazer upload nesta pasta.');
        }

        $file        = $request->file('file');
        $nomeDisco   = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $caminho     = "file-manager/{$companyId}/{$nomeDisco}";

        Storage::disk('local')->put($caminho, file_get_contents($file));

        $arquivo = FmArquivo::create([
            'company_id'    => $companyId,
            'pasta_id'      => $pasta->id,
            'nome_original' => $file->getClientOriginalName(),
            'nome_disco'    => $nomeDisco,
            'tipo_mime'     => $file->getMimeType(),
            'tamanho'       => $file->getSize(),
            'caminho'       => $caminho,
            'created_by'    => $user->id,
        ]);

        return back()->with('success', "Arquivo '{$arquivo->nome_original}' enviado com sucesso.");
    }

    // ─────────────────────────────────────────────────────────────
    // DOWNLOAD
    // ─────────────────────────────────────────────────────────────

    public function download(Request $request, int $id)
    {
        $user      = $request->user();
        $companyId = $this->resolveCompanyId($request);

        $arquivo = FmArquivo::where('company_id', $companyId)->findOrFail($id);

        if ($arquivo->pasta_id) {
            $pasta = FmPasta::find($arquivo->pasta_id);
            if ($pasta && !$this->canInFolder($user, $pasta, 'visualizar')) {
                abort(403);
            }
        }

        if (!Storage::disk('local')->exists($arquivo->caminho)) {
            abort(404, 'Arquivo não encontrado no storage.');
        }

        return Storage::disk('local')->download($arquivo->caminho, $arquivo->nome_original);
    }

    // ─────────────────────────────────────────────────────────────
    // PASTAS
    // ─────────────────────────────────────────────────────────────

    public function storeFolder(Request $request)
    {
        $user      = $request->user();
        $companyId = $this->resolveCompanyId($request);

        $data = $request->validate([
            'nome'      => 'required|string|max:255',
            'parent_id' => 'nullable|integer',
        ]);

        $parentId = $data['parent_id'] ?? null;
        if ($parentId) {
            $parent = FmPasta::where('company_id', $companyId)->findOrFail($parentId);
            if (!$this->canInFolder($user, $parent, 'incluir')) {
                abort(403, 'Sem permissão para criar pasta aqui.');
            }
        } else {
            // Criar na raiz — busca a pasta raiz
            $root = FmPasta::where('company_id', $companyId)->where('is_root', true)->first();
            $parentId = $root?->id;
        }

        FmPasta::create([
            'company_id' => $companyId,
            'parent_id'  => $parentId,
            'nome'       => $data['nome'],
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        return back()->with('success', "Pasta '{$data['nome']}' criada com sucesso.");
    }

    public function destroyFolder(Request $request, int $id)
    {
        $user      = $request->user();
        $companyId = $this->resolveCompanyId($request);

        $pasta = FmPasta::where('company_id', $companyId)->where('is_root', false)->findOrFail($id);

        if (!$this->canInFolder($user, $pasta, 'excluir')) {
            abort(403, 'Sem permissão para excluir esta pasta.');
        }

        $pasta->delete(); // Soft delete

        return back()->with('success', "Pasta '{$pasta->nome}' movida para a lixeira.");
    }

    // ─────────────────────────────────────────────────────────────
    // ARQUIVOS
    // ─────────────────────────────────────────────────────────────

    public function destroyFile(Request $request, int $id)
    {
        $user      = $request->user();
        $companyId = $this->resolveCompanyId($request);

        $arquivo = FmArquivo::where('company_id', $companyId)->findOrFail($id);

        if ($arquivo->pasta_id) {
            $pasta = FmPasta::find($arquivo->pasta_id);
            if ($pasta && !$this->canInFolder($user, $pasta, 'excluir')) {
                abort(403, 'Sem permissão para excluir este arquivo.');
            }
        }

        $arquivo->delete(); // Soft delete

        return back()->with('success', "Arquivo '{$arquivo->nome_original}' movido para a lixeira.");
    }

    public function toggleStar(Request $request, int $id)
    {
        $companyId = $this->resolveCompanyId($request);

        $arquivo = FmArquivo::where('company_id', $companyId)->findOrFail($id);
        $this->requireFilePermission($request->user(), $arquivo, 'visualizar');
        $arquivo->update(['is_starred' => !$arquivo->is_starred]);

        return back();
    }

    // ─────────────────────────────────────────────────────────────
    // LIXEIRA
    // ─────────────────────────────────────────────────────────────

    public function restoreFile(Request $request, int $id)
    {
        $companyId = $this->resolveCompanyId($request);

        $arquivo = FmArquivo::onlyTrashed()->where('company_id', $companyId)->findOrFail($id);
        $this->requireFilePermission($request->user(), $arquivo, 'excluir');
        $arquivo->restore();

        return back()->with('success', 'Arquivo restaurado.');
    }

    public function restoreFolder(Request $request, int $id)
    {
        $companyId = $this->resolveCompanyId($request);

        $pasta = FmPasta::onlyTrashed()->where('company_id', $companyId)->findOrFail($id);
        if (!$this->canInFolder($request->user(), $pasta, 'excluir')) {
            abort(403, 'Você não tem permissão para restaurar esta pasta.');
        }
        $pasta->restore();

        return back()->with('success', 'Pasta restaurada.');
    }

    public function forceDeleteFile(Request $request, int $id)
    {
        $companyId = $this->resolveCompanyId($request);

        $arquivo = FmArquivo::onlyTrashed()->where('company_id', $companyId)->findOrFail($id);
        $this->requireFilePermission($request->user(), $arquivo, 'excluir');

        // Remove do storage
        if (Storage::disk('local')->exists($arquivo->caminho)) {
            Storage::disk('local')->delete($arquivo->caminho);
        }

        $arquivo->forceDelete();

        return back()->with('success', 'Arquivo excluído permanentemente.');
    }

    // ─────────────────────────────────────────────────────────────
    // ACESSO POR EMPRESA (master_admin only)
    // ─────────────────────────────────────────────────────────────

    public function empresaAcesso(Request $request)
    {
        if (!$request->user()->is_master_admin) abort(403);

        $companies = Company::select('id', 'nome_fantasia', 'razao_social')
            ->orderBy('nome_fantasia')
            ->get()
            ->map(function ($c) {
                $c->fm_habilitado = DB::table('fm_empresa_acesso')->where('company_id', $c->id)->exists();
                return $c;
            });

        return Inertia::render('FileManager/EmpresaAcesso', [
            'companies' => $companies,
        ]);
    }

    public function toggleEmpresaAcesso(Request $request, int $companyId)
    {
        if (!$request->user()->is_master_admin) abort(403);

        Company::findOrFail($companyId);

        $exists = DB::table('fm_empresa_acesso')->where('company_id', $companyId)->exists();

        if ($exists) {
            DB::table('fm_empresa_acesso')->where('company_id', $companyId)->delete();
            $msg = 'Acesso ao gerenciador de arquivos revogado.';
        } else {
            DB::table('fm_empresa_acesso')->insert([
                'company_id'    => $companyId,
                'habilitado_por' => $request->user()->id,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
            $msg = 'Acesso ao gerenciador de arquivos liberado.';
        }

        return back()->with('success', $msg);
    }

    // ─────────────────────────────────────────────────────────────
    // GRUPOS DE ACESSO
    // ─────────────────────────────────────────────────────────────

    public function grupos(Request $request)
    {
        $this->requireFileManagerAdmin($request->user());
        $companyId = $this->resolveCompanyId($request);

        $grupos = FmGrupo::where('company_id', $companyId)
            ->with(['users:id,name,email', 'pastas:id,nome'])
            ->orderBy('nome')
            ->get();

        $users  = User::where('company_id', $companyId)->select('id', 'name', 'email')->orderBy('name')->get();
        $pastas = FmPasta::where('company_id', $companyId)->where('is_root', false)->orderBy('nome')->get(['id', 'nome']);

        return Inertia::render('FileManager/Grupos', [
            'grupos' => $grupos,
            'users'  => $users,
            'pastas' => $pastas,
        ]);
    }

    public function storeGrupo(Request $request)
    {
        $this->requireFileManagerAdmin($request->user());
        $companyId = $this->resolveCompanyId($request);

        $data = $request->validate(['nome' => 'required|string|max:100']);

        FmGrupo::create([
            'company_id' => $companyId,
            'nome'       => $data['nome'],
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', "Grupo '{$data['nome']}' criado.");
    }

    public function destroyGrupo(Request $request, int $id)
    {
        $this->requireFileManagerAdmin($request->user());
        $companyId = $this->resolveCompanyId($request);

        FmGrupo::where('company_id', $companyId)->findOrFail($id)->delete();

        return back()->with('success', 'Grupo excluído.');
    }

    public function addUserToGrupo(Request $request, int $grupoId)
    {
        $this->requireFileManagerAdmin($request->user());
        $companyId = $this->resolveCompanyId($request);
        $grupo     = FmGrupo::where('company_id', $companyId)->findOrFail($grupoId);

        $data = $request->validate([
            'user_id'               => [
                'required',
                'integer',
                \Illuminate\Validation\Rule::exists('users', 'id')->where('company_id', $companyId),
            ],
            'pode_adicionar_membros' => 'boolean',
            'pode_remover_membros'   => 'boolean',
        ]);

        DB::table('fm_grupo_users')->updateOrInsert(
            ['grupo_id' => $grupo->id, 'user_id' => $data['user_id']],
            [
                'pode_adicionar_membros' => $data['pode_adicionar_membros'] ?? false,
                'pode_remover_membros'   => $data['pode_remover_membros'] ?? false,
                'created_at'             => now(),
                'updated_at'             => now(),
            ]
        );

        return back()->with('success', 'Usuário adicionado ao grupo.');
    }

    public function removeUserFromGrupo(Request $request, int $grupoId, int $userId)
    {
        $this->requireFileManagerAdmin($request->user());
        $companyId = $this->resolveCompanyId($request);
        $grupo     = FmGrupo::where('company_id', $companyId)->findOrFail($grupoId);

        User::where('company_id', $companyId)->findOrFail($userId);
        DB::table('fm_grupo_users')->where('grupo_id', $grupo->id)->where('user_id', $userId)->delete();

        return back()->with('success', 'Usuário removido do grupo.');
    }

    public function setPastaPermission(Request $request, int $grupoId)
    {
        $this->requireFileManagerAdmin($request->user());
        $companyId = $this->resolveCompanyId($request);
        $grupo     = FmGrupo::where('company_id', $companyId)->findOrFail($grupoId);

        $data = $request->validate([
            'pasta_id'       => [
                'required',
                'integer',
                \Illuminate\Validation\Rule::exists('fm_pastas', 'id')->where('company_id', $companyId),
            ],
            'pode_visualizar' => 'boolean',
            'pode_incluir'    => 'boolean',
            'pode_excluir'    => 'boolean',
        ]);

        DB::table('fm_pasta_grupos')->updateOrInsert(
            ['pasta_id' => $data['pasta_id'], 'grupo_id' => $grupo->id],
            [
                'pode_visualizar' => $data['pode_visualizar'] ?? true,
                'pode_incluir'    => $data['pode_incluir'] ?? false,
                'pode_excluir'    => $data['pode_excluir'] ?? false,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]
        );

        return back()->with('success', 'Permissões atualizadas.');
    }
}
