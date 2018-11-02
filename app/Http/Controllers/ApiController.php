<?php

namespace App\Http\Controllers;

class ApiController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Index page. Shows the application name.
     *
     * @return string
     */
    public function index() {
        return env('APP_NAME', 'Laravel Lumen');
    }

    /**
     * Submit a new job request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function submitJobRequest() {
        $output = []; // todo



        return response()->json($output);
    }


    /**
     * Get the status of a job by jobID
     *
     * @param $jobID
     * @return \Illuminate\Http\JsonResponse
     */
    public function getJobStatus($jobID) {
        $output = []; // todo



        return response()->json($output);
    }

}
