<?php

namespace App\Http\Controllers\Api;

use App\Models\HistoryPhoto;
use App\Models\SharingPhoto;
use App\Traits\ReturnResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;


class PhotoController extends Controller
{
    use ReturnResponse;

    public function __construct()
    {
        $this->middleware('auth:api')->only(['store', 'update', 'destroy', 'like', 'unlike']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $per_page = $request->input('per_page');

        $lists      = $per_page == NULL ? SharingPhoto::latest()->get() : SharingPhoto::latest()->paginate($per_page);

        return $this->success($lists, 'Data berhasil ditampilkan');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validasi = Validator::make($request->all(), [
            'title'  => 'required|string',
            'image'  => 'required|mimes:png,jpg|max:1000',
            'caption'=> 'required|string',
            'tags'   => 'required'
        ]);

        if ($validasi->fails()) {
            return $this->failed(null, $validasi->errors());
        }else{
            $data   = $request->all();
            $image  = $request->file('image');
            DB::beginTransaction();
            
            $image->storeAs('public/sharing-photo', $image->hashName());
            $data['image'] = $image->hashName();
            $result =  SharingPhoto::create($data);

            DB::commit();
            if ($result) {
                return $this->success($result, 'Data Sharing Photo berhasil ditambahkan');
            }
            return $this->failed(null, 'Data gagal ditambahkan');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $detail = SharingPhoto::find($id);

        if ($detail) {
            return $this->success($detail, 'Detail Sharing Photo ditemukan');
        }
        return $this->failed(null, 'Detail Sharing Photo gagal ditemukan');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validasi = Validator::make($request->all(), [
            'title'  => 'required|string',
            'caption'=> 'required|string',
            'tags'   => 'required'
        ]);

        if ($validasi->fails()) {
            return $this->failed(null, $validasi->errors());
        }else{
            $data   = $request->all();
            $image  = $request->file('image');
            $detail = SharingPhoto::find($id);
            if ($detail) {
                DB::beginTransaction();
                if ($image) {
                    //hapus image lama
                    Storage::disk('local')->delete('public/products/'.basename($detail->image));
                    $image->storeAs('public/sharing-photo', $image->hashName());
                    $data['image'] = $image->hashName();
                }
                
                $result =  $detail->update($data);
    
                DB::commit();
                if ($result) {
                    return $this->success($data, 'Data Sharing Photo berhasil diperbarui');
                }
                return $this->failed(null, 'Data gagal diperbarui');
            }else{
                return $this->failed(null, 'Data tidak ditemukan');
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $detail = SharingPhoto::find($id);

        if ($detail) {
            $detail->delete();
            return $this->success($detail, 'Detail Sharing Photo berhasil dihapus');
        }
        return $this->failed(null, 'Detail Sharing Photo gagal ditemukan');
    }

    /**
     * @param  int  like $id
     * @return \Illuminate\Http\Response
     */
    public function like(Request $request, $id){
        $validasi = Validator::make($request->all(), [
            'user_id'  => 'required|numeric',
        ]);

        if ($validasi->fails()) {
            return $this->failed(null, $validasi->errors());
        }else{
            $detail = SharingPhoto::find($id);
            if ($detail) {
                $user_id = $request->input('user_id');
                $check = HistoryPhoto::where('photo_id', $id)->where('user_id', $user_id)->first();   
                if ($check) {
                    return $this->failed($check, 'Anda sudah menyukai photo '.$check->relatedPhoto->title);
                }else{
                    DB::beginTransaction();
                    $result = HistoryPhoto::create([
                        'photo_id'  => $id,
                        'user_id'   => $user_id
                    ]);
                    $detail->update([
                        'like'  => $detail->relatedHistory->count()
                    ]);
                    DB::commit();
                    return $this->success($result, 'Berhasil menyukai photo '.$result->relatedPhoto->title);
                }
            }
            return $this->failed(null, 'Detail Sharing Photo gagal ditemukan');
        }
    }

     /**
     * @param  int  like $id
     * @return \Illuminate\Http\Response
     */
    public function unlike(Request $request, $id){
        $validasi = Validator::make($request->all(), [
            'user_id'  => 'required|numeric',
        ]);

        if ($validasi->fails()) {
            return $this->failed(null, $validasi->errors());
        }else{
            $detail = SharingPhoto::find($id);
            if ($detail) {
                $user_id = $request->input('user_id');
                $check = HistoryPhoto::where('photo_id', $id)->where('user_id', $user_id)->first();   
                if ($check) {
                    DB::beginTransaction();
                    $check->delete();
                    $detail->update([
                        'like'  => $detail->relatedHistory->count()
                    ]);
                    DB::commit();
                    return $this->success($check, 'Anda telah unlike photo '.$detail->title);
                }else{
                    return $this->failed($check, 'Anda belum menyukai photo '.$detail->title);
                }
            }
            return $this->failed(null, 'Detail Sharing Photo gagal ditemukan');
        }
    }

    
}
