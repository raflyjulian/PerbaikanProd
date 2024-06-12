<?php

namespace App\Http\Controllers;

use App\Models\Lending;
use App\Models\StuffStock;

use App\Helpers\ApiFormatter;
use Illuminate\Http\Request;

class LendingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try{
            $getLending = Lending::with('stuff', 'user', 'restoration')->get();

            return ApiFormatter::sendResponse(200, 'Successfully Get All Lending Data', $getLending);
        }catch (\Exception $e) {
            return ApiFormatter::sendResponse(400, $e->getMessage());   
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
                'date_time' => 'required',
                'name' => 'required',
                'user_id' => 'required',
                'notes' => 'required',
                'total_stuff' => 'required',
            ]);

            $createLending = Lending::create([
                'stuff_id' => $request->stuff_id,
                'date_time' => $request->date_time,
                'name' => $request->name,
                'user_id' => $request->user_id,
                'notes' => $request->notes,
                'total_stuff' => $request->total_stuff,
            ]);

            $getStuffStock = StuffStock::where('stuff_id', $request->stuff_id)->first();
            $updateStock = $getStuffStock->update([
                'total_available' => $getStuffStock['total_available'] - $request->total_stuff,
            ]);

            return ApiFormatter::sendResponse(200, 'Successfully Create A lending data', $createLending);
        }catch (\Exception $e) {
            return ApiFormatter::sendResponse(400, $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Lending  $lending
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try{
            $getLending = Lending::where('id', $id)->with('stuff', 'user', 'restoration')->first();

            if (!$getLending){
                return ApiFormatter::sendResponse(404, 'Data Lending Not Found');
            }else{
                return ApiFormatter::sendResponse(200, 'Successfully Get A Lending data', $getLending);
            }
        }catch(\Exception $e ){
            return ApiFormatter::sendResponse(400, $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Lending  $lending
     * @return \Illuminate\Http\Response
     */
    public function edit(Lending $lending)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Lending  $lending
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request,$id)
    {
        try{
            $getLending = Lending::find($id);

            if ($getLending) {
                    $this->validate($request, [
                    'stuff_id' => 'required',
                    'date_time' => 'required',
                    'name' => 'required',
                    'user_id' => 'required',
                    'notes' => 'required',
                    'total_stuff' => 'required',
                ]);

                $getStuffStock = StuffStock::where('stuff_id', $request->stuff_id)->first();//get stok berdasarkan requeststuff id
                $getCurrentStock = StuffStock::where('stuff_id', $getLending['stuff_id'])->first();// get stok berdasarkan id lending

                if ($request->stuff_id == $getCurrentStock['stuff_id']){
                    $updateStock = $getCurrentStock->update([
                        'total_available'=>$getCurrentStock['total_available'] + $getLending['total_stuff'] - $request->total_stuff,
                    ]);//total available lama akan di jumlahkan dengan total peminjaman barang lama lalu dikurangi dengan total peminjaman baru

                }else{
                    $updateStock = $getCurrentStock->update([
                        'total_available' => $getCurrentStock['total_available'] + $getLending['total_stuff'],
                    ]);//total available lama di jumlahkan dengan total pinjamman barng yg lama

                    $updateStock = $getStuffStock->update([
                        'total_available' => $getStuffStock['total_available'] - $request['total_stuff'],
                    ]);//total available baru dikurangi dngn total pinjaman baru
                }

                $updateLending = $getLending->update([
                    'stuff_id' => $request->stuff_id,
                    'date_time' => $request->date_time,
                    'name' => $request->name,
                    'user_id' => $request->user_id,
                    'notes' => $request->notes,
                    'total_stuff' => $request->total_stuff,
                ]);

                $getUpdateLending = Lending::where('id', $id)->with('stuff', 'user', 'restoration')->first();

                return ApiFormatter::sendResponse(200, 'Successfully Update A Lending data', $getUpdateLending);
                
            }

        }catch (\Exception $e){
            return ApiFormatter::sendResponse(400, $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Lending  $lending
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {

            $getLending = Lending::find($id);

            if (!$getLending) {
                return ApiFormatter::sendResponse(404, 'Data Lending Not Found');
            } else {
                if ($getLending -> restoration){
                    return ApiFormatter::sendResponse(404, 'Data Lending Sudah Memiliki Restoration');
                } else {
                    $addStock = StuffStock::where('stuff_id', $getLending['stuff_id'])->first();
                    $updateStock = $addStock->update([
                        'total_available' => $addStock['total_available'] + $getLending['total_stuff'],
                    ]);
    
                    $deleteLending = $getLending->delete();
    
                    if ($deleteLending && $updateStock) {
                        return ApiFormatter::sendResponse(200,   'Successfully Delete A Lending Data');
                    }
                }
               
            }
        } catch (\Exception $e) {   
            return ApiFormatter::sendResponse(400, $e->getMessage());
        }
    }

    public function trash()
    {
        try {

            $lendingDeleted = Lending::onlyTrashed()->get();

            if (!$lendingDeleted) {
                return ApiFormatter::sendResponse(404,'Deletd Data Lending Doesnt Exists');
            } else {
                return ApiFormatter::sendResponse(200, 'Successfully Get Delete All Lending Data', $lendingDeleted);
            }
        } catch (\Exception $e) {
            return ApiFormatter::sendResponse(400, $e->getMessage());
        }
    }

    public function restore($id)
    {
        try {

            $getLending = Lending::onlyTrashed()->where('id', $id);

            if (!$getLending) {
                return ApiFormatter::sendResponse(404, 'Restored Data Lending Doesnt Exists');
            } else {
                $restoreLending = $getLending->restore();

                if ($restoreLending) {
                    $getRestore = Lending::find($id);
                    $addStock = StuffStock::where('stuff_id', $getRestore['stuff_id'])->first();
                    $updateStock = $addStock->update([
                        'total_available' => $addStock['total_available'] - $getRestore['total_stuff'],
                    ]);

                    return ApiFormatter::sendResponse(200, 'Successfully Restore A Deleted Lending Data', $getRestore);
                }
            }
        } catch (\Exception $e) {
            return ApiFormatter::sendResponse(400, $e->getMessage());
        }
    }

    public function deletePermanent($id)
    {
        try {

            $getLending = Lending::onlyTrashed()->where('id', $id);

            if (!$getLending) {
                return ApiFormatter::sendResponse(404, 'Data Lending for Permanent Delete Doesnt Exists');
            } else {
                $forceStuff = $getLending->forceDelete();

                if ($forceStuff) {
                    return ApiFormatter::sendResponse(200, 'Successfully Permanent Delete A Lending Data');
                }
            }
        } catch (\Exception $e) {
            return ApiFormatter::sendResponse(400, $e->getMessage());
        }
    }

    public function __construct()
    {
        $this->middleware('auth:api');
    }
}
