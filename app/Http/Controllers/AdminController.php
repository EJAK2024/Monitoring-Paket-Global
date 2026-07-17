<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Container;
use App\Models\Country;
use App\Models\Port;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function dashboard()
    {
        return view('admin.index', [
            'userCount' => User::count(),
            'countryCount' => Country::count(),
            'portCount' => Port::count(),
            'articleCount' => Article::count(),
            'supplierCount' => Supplier::count(),
            'containerCount' => Container::count(),
        ]);
    }

    public function suppliers()
    {
        return view('admin.suppliers', [
            'suppliers' => Supplier::with('country')->latest()->get(),
            'countries' => Country::orderBy('name')->get(),
        ]);
    }

    public function storeSupplier(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'country_id' => 'required|exists:countries,id',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'category' => 'nullable|string|max:100',
            'reliability_score' => 'nullable|integer|min:0|max:100',
            'on_time_delivery_pct' => 'nullable|numeric|min:0|max:100',
            'quality_rating' => 'nullable|integer|min:0|max:100',
            'lead_time_days' => 'nullable|integer|min:0',
            'certification' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:active,suspended,inactive',
            'notes' => 'nullable|string',
        ]);

        Supplier::create($validated);

        return redirect()->route('admin.suppliers')->with('success', 'Supplier berhasil ditambahkan.');
    }

    public function destroySupplier(Supplier $supplier)
    {
        $supplier->delete();

        return redirect()->route('admin.suppliers')->with('success', 'Supplier berhasil dihapus.');
    }

    public function containers()
    {
        return view('admin.containers', [
            'containers' => Container::with('vessel')->latest()->paginate(20),
        ]);
    }

    public function destroyContainer(Container $container)
    {
        $container->delete();

        return redirect()->route('admin.containers')->with('success', 'Container berhasil dihapus.');
    }

    public function ports()
    {
        return view('admin.ports', [
            'ports' => Port::latest()->get(),
            'countries' => Country::orderBy('name')->get(),
        ]);
    }

    public function storePort(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'country_code' => 'nullable|string|size:2',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'port_type' => 'nullable|string|max:100',
        ]);

        Port::create($validated);

        Cache::increment('portmap_cache_version');

        return redirect()->route('admin.ports')->with('success', 'Pelabuhan berhasil ditambahkan.');
    }

    public function destroyPort(Port $port)
    {
        $port->delete();

        Cache::increment('portmap_cache_version');

        return redirect()->route('admin.ports')->with('success', 'Pelabuhan berhasil dihapus.');
    }

    public function articles()
    {
        return view('admin.articles', ['articles' => Article::latest()->paginate(20)]);
    }

    public function storeArticle(Request $request)
    {
        Article::create($request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'author' => 'nullable|string|max:255',
            'published_at' => 'nullable|date',
        ]));

        return redirect()->route('admin.articles')->with('success', 'Article created.');
    }

    public function destroyArticle(Article $article)
    {
        $article->delete();

        return redirect()->route('admin.articles')->with('success', 'Article deleted.');
    }

    public function users()
    {
        return view('admin.users', [
            'users' => User::latest()->paginate(20),
        ]);
    }

    public function storeUser(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'is_admin' => 'boolean',
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_admin' => $validated['is_admin'] ?? false,
        ]);

        return redirect()->route('admin.users')->with('success', 'User created.');
    }

    public function destroyUser(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users')->with('error', 'Cannot delete yourself.');
        }

        $user->delete();

        return redirect()->route('admin.users')->with('success', 'User deleted.');
    }
}
