<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Select2Controller extends Controller
{
    public function getSource(Request $request, $source)
    {
        //        dd($source, $request->all());

        if (empty($source)) {
            return Helper::errorResponse('Source key missing', 403);
        }

        $output = null;

        switch ($source) {
            case 'user':
            case 'users':
            case 'User':
            case 'Users':
                $users = User::notRole(['Root', 'Leader', 'Trainer', 'Student'])
                    ->select(['id', DB::raw("CONCAT(users.first_name,' ',users.last_name,' <',users.email,'>') as text")]);
                if ($request->has('search')) {
                    $term = trim(strtolower(urldecode($request->search)));

                    $users = $users->where(function ($query) use ($term) {
                        return $query->where('users.first_name', 'LIKE', "%{$term}%")
                            ->orWhere('users.last_name', 'LIKE', "%{$term}%")
                            ->orWhere('users.email', 'LIKE', "%{$term}%");
                        //                        return $query->whereRaw( 'LOWER(first_name) LIKE %?%', [ $term ] );
                    });

                    //                    $users = $users->whereRaw( 'LOWER(first_name) LIKE %?%', [ trim( strtolower( urldecode( $request->search ) ) ) ] )
                    //                                   ->orWhereRaw( 'LOWER(last_name) LIKE %?%', [ trim( strtolower( urldecode( $request->search ) ) ) ] )
                    //                                   ->orWhereRaw( 'LOWER(email) LIKE %?%', [ trim( strtolower( urldecode( $request->search ) ) ) ] );
                }
                $output = $users->paginate(10);

                break;
            default:
                $output = Helper::errorResponse('No response found', 404);

                break;
        }

        return $output;
    }
}
