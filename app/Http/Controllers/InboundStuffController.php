<?php

namespace App\Http\Controllers;

use App\Helpers\ApiFormatter;
use App\Models\InboundStuff;
use App\Models\Stuff;
use App\Models\StuffStock;
use Illuminate\Http\Request;


class InboundStuffController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try{

            if($request->filter_id) {
                $data = InboundStuff::where('stuff_id', $request->filter_id)->with('stuff', 'stuff.stuffStock')->get();
            }else{
                $data = InboundStuff::all();
            }
            return ApiFormatter::sendResponse(200, 'success', $data);
        }catch (\Exception $err){
            return ApiFormatter::sendResponse(400, 'bad request', $err->getMessage());
        }
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
        try{
            $this->validate($request, [
                'stuff_id' => 'required',
                'total' => 'required',
                'date' => 'required',
                'proff_file' => 'required|mimes:jpeg,png,pdf|max:2048',
            ]);

            if ($request->hasFile('proff_file')){ //ngecek ada filr apa ngga
                $proff = $request->file('proff_file'); //get filenya
                $destinationPath = 'proff/'; //sub path di folder public
                $proffName = date('YmdHis') . "." . $proff->getClientOriginalExtension(); //modifikasi nama filr, tajunbulantanggaljammenitdetik. extension
                $proff->move($destinationPath, $proffName); // file yg sudah di get diatas dipindahan ke folder public/proff dengan nama sesuai yang di variabel proffname
            }

            $createStock = InboundStuff::create([
                'stuff_id' => $request->stuff_id,
                'total' => $request->total,
                'date' => $request->date,
                'proff_file' => $proffName
            ]);

            if ($createStock){
                $getStuff = Stuff::where('id', $request->stuff_id)->first();
                $getStuffStock = StuffStock::where('stuff_id', $request->stuff_id)->first();

                if(!$getStuffStock) {
                    $updateStock = StuffStock::create([
                        'stuff_id' => $request->stuff_id,
                        'total_available' => $request->total,
                        'total_defec' => 0,
                    ]);
                } else {
                    $updateStock = $getStuffStock->update([
                        'stuff_id' => $request->stuff_id,
                        'total_available' => $getStuffStock['total_available'] + $request->total,
                        'total_defec' => $getStuffStock['total_defec'],
                    ]);
                }

                if ($updateStock) {
                    $getStock = StuffStock::where('stuff_id', $request->stuff_id)->first();
                    $stuff = [
                        'stuff' => $getStuff,
                        'inboundStuff'=> $createStock,
                        'stuffStock' => $getStock,
                    ];

                    return ApiFormatter::sendResponse(200,  'Successfully Create A inbound Stuff data', $stuff);
                } else {
                    return ApiFormatter::sendResponse(400,  'Failed To Update A s Stuff Stock data');
                }
            } else {
                return ApiFormatter::sendresponse(400, 'Failed To Create A Inbound Stuff Data');
            }
        } catch (\Exception $e){
            return ApiFormatter::sendResponse(400, false, $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\InboundStuff  $inboundStuff
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $data = InboundStuff::with('stuff')->where('id', $id)->first();

            if(is_null($data)){
                return ApiFormatter::sendResponse(400, 'bad request', 'Data not found!');
            } else {
                return ApiFormatter::sendResponse(200, 'success', $data);
            }
        } catch (\Exception $err) {

            return ApiFormatter::sendResponse(400, 'bad request', $err->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\InboundStuff  $inboundStuff
     * @return \Illuminate\Http\Response
     */
    public function edit(InboundStuff $inboundStuff)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\InboundStuff  $inboundStuff
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, InboundStuff $inboundStuff)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\InboundStuff  $inboundStuff
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        
        try {  
            $inboundData = InboundStuff::where('id', $id) -> first();  
            // simpan data dr inbound yg diperlukan/akan digunakan nnti setelah delete  
            $stuffId = $inboundData['stuff_id'];  
            $totalInbound = $inboundData['total'];  
            $inboundData -> delete();  
            // kurangi total_available sblmnya dengan total dr inbound dihps  
            $dataStock = StuffStock::where('stuff_id', $inboundData['stuff_id']) -> first ();  
            $total_available = (int)$dataStock['total_available'] - (int)$totalInbound;  
            $minusTotalStock = $dataStock -> update(['total_available' => $total_available]);

            if ($minusTotalStock) {  
                $updatedStuffWithInboundAndStock = Stuff::where('id', $stuffId)->with('inboundStuffs', 'stuffStock')->first() ; 
                return ApiFormatter::sendResponse(200, 'success', $updatedStuffWithInboundAndStock);  
            }  
        } catch ( Exception $err) {  
            return ApiFormatter::sendResponse(400, 'bad request', $err->getMessage());  
        }  
    }

    public function  trash()
    {
        try {
            $data = InboundStuff::onlyTrashed()->get();

            return ApiFormatter::sendResponse(200, 'success', $data);
        } catch (\Exception $err){
            return ApiFormatter::sendResponse(400, 'bad request', $err->getMessage());
        }

      
    }

    public function restore($id)
    {
        try{
            $checkProses = InboundStuff::onlyTrashed()->where('id', $id)->restore();

            if ($checkProses){
                $data = InboundStuff::find($id);

                $totalRestore = $data->total;
                
                
                $stuffId = $data->stuff_id;

                $stuffStock = StuffStock::where('stuff_id', $stuffId)->first();

                if ($stuffStock){
                    $stuffStock ->total_available += $totalRestore;
                    $stuffStock->save();
                }

                return ApiFormatter::sendResponse(200, 'success', $data);
            } else {
                return ApiFormatter::sendResponse(400, 'bad request', 'Gagal mengembalikan data!');
            }
        } catch (\Exception $err) {
            return ApiFormatter::sendResponse(400, 'bad request', $err->getMessage());
        }
    }

    public function deletePermanent($id)
    {
        try {

            $getInbound = InboundStuff::onlyTrashed()->where('id',$id)->first();

            unlink(base_path('public/proff/'.$getInbound->proff_file));
            $checkProses = InboundStuff::onlyTrashed()->where('id', $id)->forceDelete();

            return ApiFormatter::sendResponse(200, 'success', 'Berhasil menghapus permanen data inbound!');
        } catch (\Exception $err) {
            return ApiFormatter::sendResponse(400, 'bad request', $err->getMessage());
        }
    }

    private function deleteAssociatedFile(InboundStuff $inboundStuff)
    {
        // Mendapatkan jalur lengkap ke direktori public
        $publicPath = $_SERVER['DOCUMENT_ROOT'] . '/public/proff';

    
        // Menggabungkan jalur file dengan jalur direktori public
         $filePath = public_path('proff/'.$inboundStuff->proff_file);
    
        // Periksa apakah file ada
        if (file_exists($filePath)) {
            // Hapus file jika ada
            unlink(base_path($filePath));
        }
    }
}
