<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

use App\Entities\Post;
use App\Services\SendMail;
use App\Http\Controllers\BaseController;
use App\Notifications\PasswordResetRequest;
use App\Repositories\Contracts\UrlRepository;
use App\Repositories\Contracts\TagRepository;
use App\Repositories\Contracts\PostRepository;
use App\Repositories\Contracts\CategoryRepository;
use App\Http\Requests\Admin\Post\CreatePostRequest;
use App\Http\Requests\Admin\Post\UpdatePostRequest;
use App\Http\Requests\Admin\Post\UploadFileRequest;

class PostController extends BaseController
{
    protected $tag;
    protected $url;
    protected $post;
    protected $paginate = 10;

    public function __construct(
        TagRepository $tag,
        UrlRepository $url,
        PostRepository $post,
        CategoryRepository $category
    ){
        $this->tag        = $tag;
        $this->url         = $url;
        $this->post        = $post;
        $this->category   = $category;
    }

    public function index(Request $request)
    {
        $posts = $this->post
        ->latest()
        ->paginate(10);

        return response()->json($posts);
    }

    public function show($id)
    {
        $post = $this->post->find($id);

        return response()->json($post);
    }

    public function store(CreatePostRequest $request)
    {
         $user = $request->user();
        $request->tag = explode(",",$request->tag);

        if(count($request->tag) > 10) {
            return $this->responseErrors('tag', 'The tag may not be greater than 10.');
        }

        $this->url->create([
            'url_title'     => $request->title,
            'uri'           => $request->uri_post,
        ]);

        $image  = $this->saveImage($request->avatar_post ,'public/images/avatar_post');
        $post   = $this->post->create([
            'content'       => $request->content,
            'title'         => $request->title,
            'status'        => ($request->status == true) ? Post::STATUS['ACTIVE'] : Post::STATUS['INACTIVE'],
            'avatar_post'   => $image,
            'uri_post'      => $request->uri_post,
            'category_id'   => $request->category_id,
            'user_id'       => $user->id,
        ]);

        $this->checkAndGenerateTag($request->tag, $post->id);

        return $this->responses(trans('notication.create.success'), Response::HTTP_OK, $post);
    }

    public function uploadFile(UploadFileRequest $request)
    {
        if(empty($request->avatar_post)) {
            $url = '';
        } else {
            $path = $request->avatar_post->store('public/images/avatar_post');
            $url = Storage::url($path);
        }

        return response()->json($url, 200);
    }

    public function update(UpdatePostRequest $request, $id)
    {
        $user = $request->user();
        $post = $this->post->find($id);

        if(empty($post)) {
            return $this->responseErrors('Not found', 'Server Not Found.');
        }

        //check url exists
        if($this->url->findByUri($request->uri_post) && $request->uri_post != $post->uri_post) {
            return $this->responseErrors('uri_post', 'The uri post has already been taken.');
        }

        if(!empty($request->avatar_post)) {
            Storage::delete($post->avatar_post);

            $post->avatar_post  = $request->avatar_post;
        }

        if(count($request->tag) > 10) {
            return $this->responseErrors('tag', 'The tag may not be greater than 10.');
        }

        $url = $this->url->findByUri($post->uri_post);
        $url->url_title     = $request->title;
        $url->uri           = $request->uri_post;
        $url->save();

        $post->content      = $request->content;
        $post->title        = $request->title;
        $post->status       = ($request->status == true) ? Post::STATUS['ACTIVE'] : Post::STATUS['INACTIVE'];
        $post->uri_post     = $request->uri_post;
        $post->category_id  = $request->category_id;
        $post->user_id      = $user->id;

        $post->save();

        $post->tags()->detach();
        $this->checkAndGenerateTag($request->tag, $post->id);

        return $this->responses(trans('notication.edit.success'), Response::HTTP_OK);
    }

    public function edit($id)
    {
        $post = $this->post->with('tags')->find($id);

        if(empty($post)) {
            return response()->json([
                'message'     => 'Incorect route',
                'status'      => Response::HTTP_NOT_FOUND
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json($post);
    }

    public function checkAndGenerateTag($tag, $post_id) {
        $tags = $this->tag->all();

        //check tag exist
        foreach($tag as $item) {
            if(!$tags->contains('tag', $item)) {
                $this->tag->create(['tag' => $item]);
            }

            $this->tag->findByTag($item)
            ->posts()
            ->attach($post_id);
        }
    }

    public function destroy($id)
    {
        $post = $this->post->find($id);
        if (empty($post)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['loi']);
        }

        $this->url->findByUri($post->uri_post)->delete();

        $post->delete();

        return $this->responses(trans('notication.delete.success'), Response::HTTP_OK);
    }

}