<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Country;
use App\Models\Port;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function dashboard()
    {
        return view('admin.index', [
            'userCount' => User::count(),
            'countryCount' => Country::count(),
            'portCount' => Port::count(),
            'articleCount' => Article::count(),
        ]);
    }

    public function ports()
    {
        return view('admin.ports', ['ports' => Port::latest()->paginate(20)]);
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
        return view('admin.users', ['users' => User::latest()->paginate(20)]);
    }
}
