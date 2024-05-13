<?php

namespace App\Http\Controllers;

use App\Helpers\ApiFormatter;
use App\Models\Stuff;
use Illuminate\Http\Request;

class StuffController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function index()
    {
        try{
         // ambil data yang mau ditampilkan
         $data = Stuff::with('StuffStock')->get();

         return ApiFormatter::sendResponse(200, 'success', $data);
                }catch (\Exception $err) {
        return ApiFormatter::sendResponse(400, 'bad request', $err->getMessage());
        }
    }

    public function store(Request $request)
    {
        try{
             // ambil data yang mau ditampilkan
            //validasi
            //'nama column'=>'validasi'
            $this->validate($request, [
                'name'=> 'required|min:3',
                'category'=>'required',
            ]);

            // proses tambah data
            //Namamodel::create([ 'column' => $request->name_or_key, ])
            $prosesData = Stuff::create([
                'name'=> $request->name,
                'category'=> $request->category,
            ]);

            //memunculkan data yg di proses masuk apa 
            if($prosesData){
                return ApiFormatter::sendResponse(200, 'success', $prosesData);
            }else{
                return ApiFormatter::sendResponse(400, 'bad request', 'Gagal memproses tambah data stuff! silahkan coba lagi.');
            }

          //memunculkan data yg di proses masuk apa 
        } catch (\Exception $err){
            return ApiFormatter::sendResponse(400, 'bad request', $err->getMessage());
        }
    }

    public function show($id) 
    {
        try{
           //$data = Stuff::where('id', $id)->first();
           $data = Stuff::find();
           // first() : kalau gada datanya, tetep success tapi data null atau kosong
           // firstOrFail() : kalau gada datanya, munculnya error
           // find() : (mencari berdasarkan primary key) munculnya sama kaya firs tapi penulisan codinganya lebih singkat $data = Stuff::find();
           // where (): (mencari column spesific terntentu) Stuff::where('id', $id)->first();
            
            return ApiFormatter::sendResponse(200, 'success', $data);
        } catch (\Exception $err){
            return ApiFormatter::sendResponse(400, 'bad request', $err->getMessage());
        }   
    }
                            // ngambil data yang di input
    public function update(Request $request, $id) {
        try {
            $this->validate($request, [
                'name'=> 'required',
                'category'=>'required',
            ]);

            $checkProsess = Stuff::where('id', $id)->update([
                'name'=> $request->name,
                'category'=> $request->category,
            ]);

            if ($checkProsess) {
                // ::create([]) : menghasilkan data yang di tambah
                // ::update([]) : menghasilkan boolean, jadi buat ambil data terbaru di cari lagi
                $data = Stuff::where('id', $id)->first();
                return ApiFormatter::sendResponse(200, 'success', $data);
            }

        } catch (\Exception $err) {
            return ApiFormatter::sendResponse(400, 'bad request', $err->getMessage());
        }
    }

    public function destroy($id)
{
    try {
        $checkProses = Stuff::findOrFail($id);

        if ($checkProses->inboundStuffs()->exists()) {
            return ApiFormatter::sendResponse(400, "bad request", "Tidak dapat menghapus data stuff, sudah terdapat data inbound");
        } 
        elseif ($checkProses->stuffStocks()->exists()) {
            return ApiFormatter::sendResponse(400, "bad request", "Tidak dapat menghapus data stuff, sudah terdapat data stuff stock");
        }
        elseif($checkProses->lendings()->exists()) {
            return ApiFormatter::sendResponse(400, "bad request", "Tidak dapat menghapus data stuff, sudah terdapat data lending");
        }
        elseif($checkProses->outboundStuffs()->exists()) {
            return ApiFormatter::sendResponse(400, "bad request", "Tidak dapat menghapus data stuff, sudah terdapat data outbound");
        }
        else {
            $checkProses->delete();
            return ApiFormatter::sendResponse(200, true, "berhasil hapus data barang dengan id $id", ['id' => $id]);
        }

    } catch (\Exception $err) {
        return ApiFormatter::sendResponse(400, 'bad request', $err->getMessage());
    }
}

    public function trash() {
        try {
            // onlyTrashed() : memanggil data sampah yang sudah di hapus/ deleted_at nya terisi
            $data = Stuff::onlyTrashed()->get();

                return ApiFormatter::sendResponse(200, 'success', $data);
        } catch (\Exception $err) {
            return ApiFormatter::sendResponse(400, 'bad request', $err->getMessage());
        }
    }

    public function restore($id) {
        try {
            // restore : mengembalikan data spesifik yang dihapus/mengahpus deleted_at nya
            $checkRestore = Stuff::onlyTrashed()->where('id', $id)->restore();

            if ($checkRestore) {
                $data = Stuff::where('id', $id)->first();
                return ApiFormatter::sendResponse(200, 'success', $data);
            }
        } catch (\Exception $err) {
            return ApiFormatter::sendResponse(400, 'bad request', $err->getMessage());
        }
    }

    public function permanentDelete($id) {
        try {
            $inboundStuff = InboundStuff::onlyTrashed()->where('id', $id)->first();

            Storage::delete($inboundStuff->file_path);
    
            $checkPermanentDelete = $inboundStuff->forceDelete();
    
            if ($checkPermanentDelete) {
                return ApiFormatter::sendResponse(200, 'success', "Berhasil menghapus permanen data Inbound Stuff dan file terkait!");
            }
        } catch (\Exception $err) {
            return ApiFormatter::sendResponse(400, 'bad request', $err->getMessage());
        }
    }
    
}