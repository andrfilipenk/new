<?php

namespace Modules\CMS\Controllers;

use Core\Request;
use Core\Response;
use Modules\CMS\Models\Post;
use Modules\CMS\Models\Category;
use Modules\CMS\Models\Tag;
use Core\Validation\Validator;
use App\Services\AuditService;

/**
 * Post Controller
 * 
 * Handles blog post management with publish workflow
 */
class PostController
{
    public function index(Request $request): Response
    {
        $status = $request->get('status');
        
        $query = \Core\Database\DB::table('cms_posts');
        
        if ($status) {
            $query->where('status', '=', $status);
        }
        
        $posts = $query->orderBy('created_at', 'DESC')->get();
        $posts = array_map(fn($row) => Post::hydrate($row), $posts);
        
        if ($request->expectsJson()) {
            return Response::json(['posts' => $posts]);
        }
        
        return view('cms/posts/index', ['posts' => $posts, 'status' => $status]);
    }
    
    public function create(): Response
    {
        $categories = Category::all();
        $tags = Tag::all();
        
        return view('cms/posts/create', ['categories' => $categories, 'tags' => $tags]);
    }
    
    public function store(Request $request): Response
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:200',
            'content' => 'required',
            'excerpt' => 'max:500',
            'status' => 'required|in:draft,published,scheduled',
            'categories' => '',
            'tags' => '',
        ]);
        
        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return Response::json(['errors' => $validator->errors()], 422);
            }
            return redirect('/cms/posts/create')->with('errors', $validator->errors());
        }
        
        try {
            $data = $validator->validated();
            $data['slug'] = $this->generateSlug($data['title']);
            $data['author_id'] = auth()->id();
            
            if ($data['status'] === 'published') {
                $data['published_at'] = date('Y-m-d H:i:s');
            }
            
            $post = Post::create($data);
            
            // Sync categories and tags
            $postModel = Post::findOrFail($post['id']);
            if (!empty($data['categories'])) {
                $postModel->syncCategories(json_decode($data['categories'], true));
            }
            if (!empty($data['tags'])) {
                $postModel->syncTags(json_decode($data['tags'], true));
            }
            
            $postModel->createRevision(auth()->id(), 'created');
            
            AuditService::logCreate('Post', $post['id'], $data, auth()->id());
            
            if ($request->expectsJson()) {
                return Response::json(['post' => $post, 'message' => 'Post created successfully'], 201);
            }
            
            return redirect('/cms/posts/' . $post['id'])->with('success', 'Post created successfully');
        } catch (\Exception $e) {
            logger()->error('Post creation failed', ['error' => $e->getMessage()]);
            
            if ($request->expectsJson()) {
                return Response::json(['error' => 'Failed to create post'], 500);
            }
            
            return redirect('/cms/posts/create')->with('error', 'Failed to create post');
        }
    }
    
    public function show(Request $request, int $id): Response
    {
        $post = Post::findOrFail($id);
        
        if ($request->expectsJson()) {
            return Response::json(['post' => $post]);
        }
        
        return view('cms/posts/show', ['post' => $post]);
    }
    
    public function publish(Request $request, int $id): Response
    {
        try {
            $post = Post::findOrFail($id);
            
            $post->update([
                'status' => 'published',
                'published_at' => date('Y-m-d H:i:s'),
            ]);
            
            return Response::json(['message' => 'Post published successfully']);
        } catch (\Exception $e) {
            logger()->error('Post publish failed', ['error' => $e->getMessage(), 'post_id' => $id]);
            return Response::json(['error' => 'Failed to publish post'], 500);
        }
    }
    
    private function generateSlug(string $title): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        return $slug;
    }
}
