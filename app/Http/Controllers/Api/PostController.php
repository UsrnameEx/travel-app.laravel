<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\JsonResponse;

class PostController extends Controller
{
    public function createPost(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|min:4|max:70',
            'short_description' => 'required',
            'full_description' => 'required',
            'images' => 'required|array',
            'is_active' => 'boolean'
        ]);

        $validator->stopOnFirstFailure(true);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()->toArray()
            ]);
        }

        $isActive = $request->post('is_active');
        if (!$isActive) {
            $isActive = true;
        }

        Post::create([
            'title' => $request->post('title'),
            'short_description' => $request->post('short_description'),
            'full_description' => $request->post('full_description'),
            'images' => $request->post('images'),
            'is_active' => $isActive,
        ]);

        return response()->json([
            'status' => 'ok'
        ]);
    }

    public function getPostById(Request $request): JsonResponse
    {
        $request->validate([
            'postId' => 'required|int'
        ]);

        $postItem = Post::firstWhere(['id' => $request->post('postId')]);
        $postItem = $this->handlePost($postItem);

        return response()->json([
            'status' => 'ok',
            'item' => $postItem
        ]);
    }

    public function getPosts(Request $request): JsonResponse
    {
        $perPage = $request->post('perPage');
        if (!$perPage) {
            $perPage = 15;
        }

        $page = $request->post('page');
        if (!$page) {
            $page = 1;
        }

        $postsPaginate = Post::paginate($perPage, page: $page);

        $posts = $postsPaginate->getCollection()->transform(function ($item) {
            return $this->handlePost($item);
        });

        return response()->json([
            'status' => 'ok',
            'items' => $posts,
            'countPages' => $postsPaginate->lastPage()
        ]);
    }

    private function getImages($images)
    {
        $arData = [];

        foreach ($images as $image) {
            $arData[] = $image['path'];
        }

        return $arData;
    }

    private function handlePost($item)
    {
        $item->images = $this->getImages($item['images']);
        $item->created = $item->created_at->format('Y-m-d H:i:s');
        $item->updated = $item->updated_at->format('Y-m-d H:i:s');
        unset($item->created_at);
        unset($item->updated_at);

        return $item;
    }
}
