<?php

namespace App\Http\Controllers;

use App\Helpers\ApiFormatter;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try{

            $data = User::all()->toArray();

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
        try {
            $this->validate($request, [
            'username' => 'required|min:3',
            'email' => 'required|email:dns',
            'password' =>'required',    
            'role' => 'required',
            ]);

            $password = substr($request->email, 0, 3) . substr($request->username, 0, 3);

            $data = User::create([
                'username' => $request->username,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
            ]);

            return ApiFormatter::sendResponse(200, 'success', $data);
        } catch (\Exception $err) {
            return ApiFormatter::sendResponse(400, 'bad request', $err->getMessage());
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
        try {
            $data = User::where('id', $id)->first();

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
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
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
        try {
            $this->validate($request, [
            'username' => 'required|min:3',
            'email' => 'required|email:dns',
            'role' => 'required',
            ]);

            $password = substr($request->email, 0, 3) . substr($request->username, 0, 3);
            if($request->password){
                $checkProses = User::where('id', $id)->update([
                    'username' => $request->username,
                    'email' => $request->email,
                    'password' => Hash::make($password),
                    'role' => $request->role,
                ]);
            }else{
                $checkProses = User::where('id', $id)->update([
                    'username' => $request->username,
                    'email' => $request->email,
                    'role' => $request->role,
                ]);
            }
            

            if ($checkProses) {
                $data = User::find($id);
                return ApiFormatter::sendResponse(200, 'success', $data);
            } else {
                return ApiFormatter::sendResponse(400, 'bad request', 'Gagal mengubah data!');
            }
        } catch (\Exception $err) {
            return ApiFormatter::sendResponse(400, 'bad request', $err->getMessage());
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
        try {
            $checkProses = User::where('id', $id)->delete();

            return ApiFormatter::sendResponse(200, 'success', 'Data User berhasil dihapus');
        } catch (\Exception $err) {
            return ApiFormatter::sendResponse(400, 'bad request', $err->getMessage());
        }
    }

    public function  trash()
    {
        try {
            $data = User::onlyTrashed()->get();

            return ApiFormatter::sendResponse(200, 'success', $data);
        } catch (\Exception $err){
            return ApiFormatter::sendResponse(400, 'bad request', $err->getMessage());
        }
    }
    public function restore($id)
    {
        try{
            $checkProses = User::onlyTrashed()->where('id', $id)->restore();

            if ($checkProses){
                $data = User::find($id);

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
            $checkProses = User::onlyTrashed()->where('id', $id)->forceDelete();

            return ApiFormatter::sendResponse(200, 'success', 'Berhasil menghapus permanen data user!');
        } catch (\Exception $err) {
            return ApiFormatter::sendResponse(400, 'bad request', $err->getMessage());
        }
    }


    public function login(Request $request)
    {
        try{
            $this->validate($request, [
                'email' => 'required',
                'password' => 'required|min:5',
            ], [
                'email.required' => 'Email harus diiisi',
                'password.required' => 'Password harus diisi',
                'password.min' => 'password minimal 5 karakter',
            ]);

            $user = User::where('email', $request->email)->first();

            if(!$user){
                return ApiFormatter::sendResponse(400, false, 'login Failed! User Doesnt Exists');
            } else {
                $isValid = Hash::check($request->password, $user->password);

                if (!$isValid){

                    //jika pw tidak cocok maka akan dikembalikan dengan respon error
                    return ApiFormatter::sendResponse(400, false, 'Login Failed Password Doesnt Match');

                } else {

                    //jika pw sesuai selanjutnya akan membuat tokoen 
                    //bin2hex digunakan untuk dapat mengonversi string karakter ASCII menjadi nilai heksdesimal
                    //random_bytes menghasilkan byte pseudo-acak yang aman secara kriptografis dengan panjang 40 karakter
                    $generateToken = bin2hex(random_bytes(40));
                    //token inilah ynag digunakan pada proses authentaication user yang login

                    $user->update([
                        'token'=> $generateToken
                        //update kolom jika token dengan value hasil dari generate token di row user yang ingin login
                    ]);

                    return ApiFormatter::sendResponse(200, 'Login Successfullly', $user);
                }
            }
        }catch (\Exception $e) {
            return ApiFormatter::sendResponse(400, false, $e->getMessage());
        }
        
    }

    public function logout(Request $request)
    {
        try {
            $this->validate($request, [
                'email' => 'required',
            ]);

            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return ApiFormatter::sendResponse(400, 'Login Failed! User Doesnt Exits');
            } else {
                if (!$user->token) {
                    return ApiFormatter::sendResponse(400, 'Logout Failed User Doesnt Login Sciene');
                }else{
                    $logout = $user->update(['token' => null]);

                    if ($logout){
                        return ApiFormatter::sendResponse(200, 'Logout Successfully');
                    }
                }
            }
        } catch(\Exception $e) {
            return ApiFormatter::sendResponse(400, false, $e->getMessage());
        }
    }

    public function __construct()
    {
        $this->middleware('auth:api');
    }
}
