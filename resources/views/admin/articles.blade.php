@extends('layouts.app')

@section('title', 'Manage Articles')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Manage Articles</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#articleModal">New Article</button>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead>
                    <tr><th>ID</th><th>Title</th><th>Author</th><th>Published</th><th>Action</th></tr>
                </thead>
                <tbody>
                    @foreach ($articles as $article)
                        <tr>
                            <td>{{ $article->id }}</td>
                            <td>{{ Str::limit($article->title, 50) }}</td>
                            <td>{{ $article->author ?? '-' }}</td>
                            <td>{{ $article->published_at ? \Carbon\Carbon::parse($article->published_at)->format('Y-m-d') : '-' }}</td>
                            <td>
                                <form action="{{ route('admin.articles.destroy', $article) }}" method="POST" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $articles->links() }}</div>
    </div>
</div>

<div class="modal fade" id="articleModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.articles.store') }}" class="modal-content">
            @csrf
            <div class="modal-header"><h5>New Article</h5></div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Title</label>
                    <input name="title" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Content</label>
                    <textarea name="content" class="form-control" rows="5" required></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Author</label>
                    <input name="author" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Published At</label>
                    <input name="published_at" type="date" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>
@endsection
