<?php

namespace App\Http\Controllers;

use App\Models\Module;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;

class ModuleController extends Controller
{
    public function index(Request $request)
    {
        // Require master admin to view modules
        $this->authorizePermission('manage-modules');

        $query = Module::with('parent')->orderBy('order');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('route_name', 'like', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->status !== 'todos') {
            $query->where('is_active', $request->status === 'ativos');
        }

        if ($request->has('visibility') && $request->visibility !== 'todos') {
            $query->where('is_visible_in_menu', $request->visibility === 'menu');
        }

        $modules = $query->get();

        return Inertia::render('Admin/Modules/Index', [
            'modules' => $modules,
            'filters' => $request->only(['search', 'status', 'visibility'])
        ]);
    }

    public function create()
    {
        $this->authorizePermission('manage-modules');

        $parents = Module::whereNull('parent_id')->orderBy('order')->get();

        return Inertia::render('Admin/Modules/Form', [
            'module' => new Module(['is_active' => true, 'is_visible_in_menu' => true]),
            'parents' => $parents
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizePermission('manage-modules');

        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:modules',
            'route_name' => 'nullable|string|max:255',
            'url' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:255',
            'parent_id' => 'nullable|exists:modules,id',
            'order' => 'integer',
        ]);

        DB::beginTransaction();
        try {
            $module = Module::create($request->all());

            // Create Spatie Permission
            Permission::firstOrCreate(['name' => $module->slug]);

            Log::info("Ação Store Module realizada pelo usuário " . auth()->id());
            DB::commit();

            return redirect()->route('admin.modules.index')->with('success', 'Módulo cadastrado com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) throw $e;
            Log::error($e->getMessage());
            return back()->with('error', 'Erro ao criar módulo.');
        }
    }

    public function edit(Module $module)
    {
        $this->authorizePermission('manage-modules');

        $parents = Module::whereNull('parent_id')
                         ->where('id', '!=', $module->id)
                         ->orderBy('order')
                         ->get();

        return Inertia::render('Admin/Modules/Form', [
            'module' => $module,
            'parents' => $parents
        ]);
    }

    public function update(Request $request, Module $module)
    {
        $this->authorizePermission('manage-modules');

        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:modules,slug,' . $module->id,
            'route_name' => 'nullable|string|max:255',
            'url' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:255',
            'parent_id' => 'nullable|exists:modules,id',
            'order' => 'integer',
        ]);

        DB::beginTransaction();
        try {
            $oldSlug = $module->slug;
            
            $module->update($request->all());

            // Update or Create Spatie Permission
            $permission = Permission::where('name', $oldSlug)->first();
            if ($permission) {
                $permission->name = $module->slug;
                $permission->save();
            } else {
                Permission::firstOrCreate(['name' => $module->slug]);
            }

            Log::info("Ação Update Module realizada pelo usuário " . auth()->id());
            DB::commit();

            return redirect()->route('admin.modules.index')->with('success', 'Módulo atualizado com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) throw $e;
            Log::error($e->getMessage());
            return back()->with('error', 'Erro ao atualizar módulo.');
        }
    }

    public function destroy(Module $module)
    {
        $this->authorizePermission('manage-modules');

        DB::beginTransaction();
        try {
            // Option to delete the permission as well or keep it. We will keep it for history, or delete it?
            // Deleting the permission deletes roles associations! Let's just delete the module.
            $module->delete();

            Log::info("Ação Destroy Module realizada pelo usuário " . auth()->id());
            DB::commit();

            return redirect()->route('admin.modules.index')->with('success', 'Módulo excluído com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) throw $e;
            Log::error($e->getMessage());
            return back()->with('error', 'Erro ao excluir módulo.');
        }
    }
}
