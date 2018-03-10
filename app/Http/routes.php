<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/


// !!NOTE DO NOT USE Controller Sub Folder with Name begin with numbers or completely numbers.

$app->get('/', function () use ($app) {
    //return $app->version();
    return response()->json(['API' => 'Version 1.0', 'App Name' => 'Swiftlet Monitoring API']);
});

$app->group(['namespace'=>'v1','prefix'=>'api/v1'], function() use ($app){
	$app->post('Register','api@register');
	$app->post('SignIn','api@signin');
	$app->post('SignInUsingCache','api@signinUsingCache');
	$app->post('AddBridge','api@addBridge');
	$app->post('AddDevice','api@addDevice');
	$app->post('ReportData','api@report');

	$app->post('MyBridge/Automate', 'api@automate');
	$app->post('MyBridge/Actuate', 'api@actuate');
	$app->get('MyBridge/{serial}/status','api@status');

	$app->get('{token}/MyBridges','api@myBridges');
	$app->get('{token}/MyBridge/{serial}/devices','api@bridgeDevices');
	$app->get('{token}/MyBridge/{bSerial}/devices/{dSerial}','api@bridgeDeviceDetail');
	$app->get('ListAllUsers','api@showAll');
});
