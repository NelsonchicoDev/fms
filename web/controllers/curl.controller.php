<?php 

class CurlController{

	/*=============================================
	Peticiones a la API propia
	=============================================*/

	static public function request($url,$method,$fields){

		$curl = curl_init();
		

		curl_setopt_array($curl, array(
			CURLOPT_URL => '/'.$url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_SSL_VERIFYHOST => FALSE,
			CURLOPT_SSL_VERIFYPEER => FALSE,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => $method,
			CURLOPT_POSTFIELDS => $fields,
			CURLOPT_HTTPHEADER => array(
				'Authorization: hdfhsdf3463463457dsfhjdfsgj45745fcgjfdgjr67'
			),
		));

		$response = curl_exec($curl);

		curl_close($curl);
		
		$response = json_decode($response);

		return $response;

	}

	/*=============================================
	Peticiones a la API DE VIMEO
	=============================================*/

	static public function getThumbnailVimeo($idVimeo){

		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => 'https://vimeo.com/api/v2/video/'.$idVimeo.'.json',
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => '',
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => 'GET',
		  CURLOPT_HTTPHEADER => array(
		    'Cookie: __cf_bm=qJRdU80JjKtOpT3pdEcnCa_eTrH12p0vhKhrkTNtL48-1724357575-1.0.1.1-D5erq9aOObDsRIc__NPI3vJPG_zOtCCWxxodxn_fmfp_6VkkuxbnWzMujNVLxyve; _abexps=%7B%223063%22%3A%2240_off%22%7D; _cfuvid=uDm6QLS7WpG5t8esAtIYtTSvxEFT_BQePQo_uITGomU-1724357575468-0.0.1.1-604800000; vuid=1688193022.37572436'
		  ),
		));

		$response = curl_exec($curl);

		curl_close($curl);

		$response = json_decode($response);

		return $response[0]->thumbnail_medium;

	}
	

}



