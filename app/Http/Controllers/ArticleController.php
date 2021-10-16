<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Attachment;
use App\Http\Requests\ArticleRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;


class ArticleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $articles = Article::all();
        return view('articles.index', compact('articles'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('articles.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ArticleRequest $request)
    {
        $article = new Article($request->all());

        $article->user_id = 1;

        $file = $request->file('file');

        DB::beginTransaction();
        try {
            $article->save();

            if (!$path = Storage::putFile('articles', $file)) {
                throw new Exception('ファイルの保存に失敗しました');
            }

            $attachment = new Attachment([
                'article_id' => $article->id,
                'org_name' => $file->getClientOriginalName(),
                'name' => basename($path),
            ]);
            //dd($attachment);
            $attachment->save();
            DB::commit();
        } catch (\Exception $e) {
            dd($e->getMessage());
            if (!empty($path)) {
                Storage::delete($path);
            }
            DB::rollback();
            return back()
                ->withErrors($e->getMessage());
        }

        return redirect()
            ->route('articles.index')
            ->with(['flash_message' => '登録が完了しました']);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Article  $article
     * @return \Illuminate\Http\Response
     */
    public function show(Article $article)
    {
        return view('articles.show', compact('article'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Article  $article
     * @return \Illuminate\Http\Response
     */
    public function edit(Article $article)
    {
        return view('articles.edit', compact('article'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Article  $article
     * @return \Illuminate\Http\Response
     */
    public function update(ArticleRequest $request, Article $article)
    {
        //dd($request);
        $article->fill($request->all());

        try {
            $article->save();
        } catch (\Exception $e) {
            return back()
                ->withErrors($e->getMessage());
        }

        return redirect()
            ->route('articles.index')
            ->with(['flash_message' => '更新が完了しました']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Article  $article
     * @return \Illuminate\Http\Response
     */
    public function destroy(Article $article)
    {
        $path = $article->image_path;
        DB::beginTransaction();
        try {
            $article->delete();
            $article->Attachment()->delete();
            if(!Storage::delete($path)) {
                throw new Exeption('ファイルの削除に失敗しました');
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors($e->getMessage());
        }

        return redirect()->route('articles.index')
            ->with(['flash_message' => '削除が完了しました']);
    }
}
