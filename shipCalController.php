<?php
/*
require_once("/easypost-php-master/lib/easypost.php");
\EasyPost\EasyPost::setApiKey('xxxxxxxxxxxxxxxxx');
*/

\EasyPost\EasyPost::setApiKey('xxxxxxxxxxxxxxxxx');



class shipCalController extends BaseController {



			public function getShippingOptions($test)
			{
				
			}

			public function domestic()
			{

				//india posts rates table.
				//inner array is weights
				//external array is zones according to distance

				$indiaPostRates = array(									
										//Zone-->//Z1 --50kms		//Z2--200Kms   //Z3-1000   //Z4--2000  //Z5--2000+
										'1' => array('1'=>'20',     '2'=>'35',    '3'=>'35',  '4'=>'35',  '5'=>'35'), // 50 grams
										'2' => array('1'=>'25',     '2'=>'35',    '3'=>'40',  '4'=>'60',  '5'=>'70'), // 200
										'3' => array('1'=>'30',     '2'=>'50',    '3'=>'60',  '4'=>'80',  '5'=>'90'), // 500
										'4' => array('1'=>'40',     '2'=>'65',    '3'=>'90',  '4'=>'120', '5'=>'140'),// 1000 
										'5' => array('1'=>'50',     '2'=>'80',    '3'=>'120', '4'=>'160', '5'=>'190'),// 1500
										'6' => array('1'=>'60',     '2'=>'95',    '3'=>'150', '4'=>'200', '5'=>'240'),// 2000
										'7' => array('1'=>'250',    '2'=>'250',   '3'=>'250', '4'=>'250', '5'=>'250') //2Kg +
									   );


				$shippingRates = array(
										"SpeedPostEMS" => "Service Not Available",
										"DTDC" =>"Service Not Available",
									  );
				$serviceTax = 1.14;

				if (Request::ajax())
				{

					
					

				    $state_id = Input::get('state_id');
				    $zip2 = Input::get('zip'); //destination zip code
				    $weight = Input::get('weight'); //weight in grams read this from cart table
				 
				    $zip1 = 560097;// Explore Embedded Zip
					
					//speed post calcuations

				    $distance = $this->getDistance($zip1, $zip2);

				    $distance = str_replace(',', '', $distance);

				    $zone = $this->getZone($distance);
				   
				    $weightCat = $this->getWeightCat($weight);

				    $shippingCostSpeedPost = $indiaPostRates[$weightCat][$zone]* $serviceTax;

				    $shippingRates['SpeedPostEMS'] = $shippingCostSpeedPost;
				    //$shippingRates['DTDC'] = 'hello';
				    $shippingRates['DTDC']= $this->getDtdcRates($state_id, $weight, $zip2);

				    return ($shippingRates);
				   //return ($distance.' falls in '.$zone.' Zone '.$weight.' falls in '.$weightCat);
				}
				elseif (Auth::user()) {
					# code...
									$zip1 = 560097;// Explore Embedded Zip
					$zip2 = 560100;

					if(Auth::user())
					{
							$zip2 = Auth::user()->pin2;


							
								$userid = Auth::user()->id;
								$cartItems = Cart::where('user_id', $userid)->where('isordered', '<' ,'1')->get();
								$cartWeight = 0;

								foreach($cartItems as $cartItem){
									 $productWeight = Product::where('id',$cartItem->product_id)->pluck('weight');
									 $productQuantity = Cart::where('product_id', $cartItem->product_id)->where('user_id', $userid)->where('isordered', '<' ,'1')->pluck('quantity');
									 //echo ($productWeight.'</br>'.$productQuantity.'</br>');
									 $cartWeight = $cartWeight + ($productWeight*$productQuantity);
									 
								}

								//echo($cartWeight);



					}
					

					$weight = $cartWeight; //change this to dynamic weight
					$state_id = User::where('id',$userid)->pluck('state2_id');

				    $distance = $this->getDistance($zip1, $zip2);

				    $distance = str_replace(',', '', $distance);

				    $zone = $this->getZone($distance);
				   
				    $weightCat = $this->getWeightCat($weight);

				    $shippingCostSpeedPost = $indiaPostRates[$weightCat][$zone]* $serviceTax;

				    $shippingRates['SpeedPostEMS'] = $shippingCostSpeedPost;
				    //$shippingRates['DTDC'] = 'hello';
				    $shippingRates['DTDC']= $this->getDtdcRates($state_id, $weight, $zip2);

				    return ($shippingRates);

				}


				else {

					//If user is not logged in find a way to return shipping rates  or cart weight to the calculator
					//infact if nothing is returned the user proceeds further, cart weight needs to calcalted from the local storage

					return $shippingRates;


				}

			}


							/* functions for SPEED POST APIs */
							public function getDistance($zip1, $zip2)
							{

								//$zip1 = 560097;
								//$zip2 = 122050;			
									
								$postcode1=($zip1);
								$postcode2=($zip2);
								 
								$url = "http://maps.googleapis.com/maps/api/distancematrix/json?origins=$postcode1+india&destinations=$postcode2+india&mode=driving&language=en-EN&sensor=false";
								 
								$data = @file_get_contents($url);
								 
								$result = json_decode($data, true);

							    //echo($result['destination_addresses'][0]);
								
							    if($result['destination_addresses'][0]!=="")
							    {
							    		foreach($result['rows'] as $distance)
										 {

										  $result = $distance['elements'][0]['distance']['text'];
										 //$result = 2500;	
										//echo 'Distance from you: ' . $distance['elements'][0]['distance']['text'] . ' (' . $distance['elements'][0]['duration']['text'] . ' in current traffic)';
										}


							    }	

							    else
							    {

							    		$result = 2500;
							    }
									

								
								


								return ($result);
								
							}


							public function getZone($distance)
							{

								   if($distance <= 50) {

								    		$zone =1 ;
									}
									elseif(($distance >= 50 )&&($distance <= 199)){

											$zone =2 ;
									}
									elseif(($distance >= 200 )&&($distance <= 999)) {
											
											$zone =3 ;
									}
									elseif(($distance >= 1000 )&&($distance <= 1999)) {
											
											$zone = 4;
									}
									else{

										    $zone = 5;	
									}

									return $zone;

							}

							public function getWeightCat($tweight)
							{

									if($tweight <= 50){
										$weightCat = 1;

									}
									elseif (($tweight>=51)&&($tweight <= 200) ){
											
										  $weightCat = 2;	
										}

									elseif (($tweight>=201)&&($tweight <= 500) ){
											
										  $weightCat = 3;	
										}

									elseif (($tweight>=501)&&($tweight <= 1000) ){
											
										  $weightCat = 4;	
										}
									elseif (($tweight>=1001)&&($tweight <= 1501) ){
											
										  $weightCat = 5;	
										}
									elseif (($tweight>=1501)&&($tweight <= 2000) ){
											
										  $weightCat = 6;	
										}

									else{

										$weightCat = 7;	
									}



									return ($weightCat);

							}



							public function getDtdcRates($state_id, $weight, $zip)
								{

									$dtdcZone;
									$weightMultiple = 1;
									$dtdcRates = array(
														 'local' => array('basic'=>'12', 'multiple'=>'10'),
														 'south'=>array('basic'=>'25', 'multiple'=>'20'),
														 'roi' => array('basic'=>'45', 'multiple'=>'30'),
													  );

									//1758:kerla 1757:Karnataka 1742: AP 1771: Tamil Nadu
									if(($zip >= 560000) && ($zip <= 560104) && ($state_id==1757) )
									{
										$dtdcZone = 'local';	
									}
									elseif(($state_id == 1758)||($state_id == 1757)||($state_id == 1742)||($state_id == 1771))
									{

								        $dtdcZone = 'south';
									}	
									else
									{
									 	$dtdcZone = 'restOfIndia';
									}	


									if($weight > 250)
									{

											$weightMultiple	 = round($weight/ 250)+1;
									}

									if($dtdcZone == 'local')
									{
										if($weightMultiple	> 1)
										{
											$rate = $dtdcRates['local']['basic']+($dtdcRates['local']['multiple']*($weightMultiple-1));	
										}
										else{
											$rate = $dtdcRates['local']['basic'];
										}
									}
									elseif($dtdcZone == 'south')
									{

										if($weightMultiple	> 1)
										{
											$rate = $dtdcRates['south']['basic']+($dtdcRates['south']['multiple']*($weightMultiple-1));	
										}
										else{
											$rate = $dtdcRates['south']['basic'];
										}

									}
									elseif($dtdcZone == 'restOfIndia')
									{

										if($weightMultiple	> 1)
										{
											$rate = $dtdcRates['roi']['basic']+($dtdcRates['roi']['multiple']*($weightMultiple-1));	
										}
										else{
											$rate = $dtdcRates['roi']['basic'];
										}

									}
									else{
										$rate = 300;
									}

									$rate = $rate * 1.45; // service tax : 14.5% + fuel surcharge 30%

									return ($rate);
								}


			

			public function international()
			{
					$shippingRatesInter = array(
										"SpeedPostEMS" => "Service Not Available",
										"DTDC" =>"Service Not Available",
										"EasyPostOptions" =>"Service Not Available",
									  );



					//for dynamic shipping calculator without user login
					if (Request::ajax()){

					$ajaxRequestFlag = 1;		
					$state_id = Input::get('state_id');
				    $zip2 = Input::get('zip'); //destination zip code
				    $weight = Input::get('weight'); //weight in grams read this from cart table
				    $country_id = Input::get('country_id'); //weight in grams read this from cart table
				
					$rateIntEms = $this->getEmsInternationalRate($country_id, $weight);	
					$shippingRatesInter['SpeedPostEMS'] = $rateIntEms;
					$rateUps = $this->getEasyPostRates($country_id, $state_id, $zip2, $weight);
					$shippingRatesInter['EasyPostOptions'] = $rateUps;
					//$shippingRatesInter['UPS'] = this-> $getEasyPostRates();


					 return ($shippingRatesInter);

					}

					//Exact rates after user has logged in

					if(Auth::user())
					{
							$zip2 = Auth::user()->pin2;


							
								$userid = Auth::user()->id;
								$cartItems = Cart::where('user_id', $userid)->where('isordered', '<' ,'1')->get();
								$cartWeight = 0;

								foreach($cartItems as $cartItem){
									 $productWeight = Product::where('id',$cartItem->product_id)->pluck('weight');
									 $productQuantity = Cart::where('product_id', $cartItem->product_id)->where('user_id', $userid)->where('isordered', '<' ,'1')->pluck('quantity');
									 //echo ($productWeight.'</br>'.$productQuantity.'</br>');
									 $cartWeight = $cartWeight + ($productWeight*$productQuantity);
									 
								}

							$country_id = Auth::user()->country2_id;		

								//echo($cartWeight);

					}
					

					$weight = $cartWeight; //change this to dynamic weight
					$rateIntEms = $this->getEmsInternationalRate($country_id, $weight);	

					$shippingRatesInter['SpeedPostEMS'] = $rateIntEms;

					//for easypost rates, a seperate function with outany user inputs. 
					return ($shippingRatesInter);

				   
				    //return ('hello');
			}

			//international rate calucation functions

				public function getEmsInternationalRate($country_id, $weight)
				{
					
						if($EmsInternationalRate  = DB::table('indiapostrates')->where('country_id','=', $country_id)->first())
						{

								$baseRate = $EmsInternationalRate->base_rate;

								$addRate = $EmsInternationalRate->additional_rate;

								$baseWeight = 250; //grams
								$weightMul = 0;
								
							
								$weightMul =  $weight / $baseWeight;

								if($weight > $baseWeight){
									$rateIntEms = ($baseRate)+ ($weightMul * $addRate);
								}else{

									$rateIntEms = ($baseRate);
								}
						

						}
						else{

							$rateIntEms = 'Service Not Available';
						}
						
						return ($rateIntEms);
						
				}	

				public function getEasyPostRates($country_id, $state_id, $zip, $weight){

					//return 'hello world';

					//weight in pounds. 

					$poundWeight = $weight*0.00220462; 

					$country  =  DB::table('countries')->where('id',$country_id)->first();
					$country_code = $country->iso_3166_2;

					$customs_item1 = \EasyPost\CustomsItem::create(array(
					  "description" => 'T-shirt',
					  "quantity" => 1,
					  "weight" => $poundWeight,
					  "value" => 11,
					  "hs_tariff_number" => 610910,
					  "origin_country" => 'IN'
					));



					$customs_info = \EasyPost\CustomsInfo::create(array(
					  "eel_pfc" => 'NOEEI 30.37(a)',
					  "customs_certify" => true,
					  "customs_signer" => 'Sandeep Patil',
					  "contents_type" => 'gift',
					  "customs_items" => array($customs_item1)
					));



					$shipment = \EasyPost\Shipment::create(array(
					  "to_address" => array(
					    'name' => '',
					    'company' => '',
					    'street1' => '',
					    'city' => '',
					    'zip' => $zip,
					    'country' => $country_code
					  ),

					  "from_address" => array(
					    'name' => 'Explore Embedded',
					    'street1' => 'F1  458 12th cross 3rd Block,',
					    'street2' => 'HMT Layout  Vidyaranyapura',
					    'city' => 'Bangalore',
					    'state' => 'Karnataka',
					    'zip' => '560097',
					    'country' => 'IN',
					    'phone' => '080-4093-8102'
					  ),
					  "parcel" => array(
					    "length" => 9,
					    "width" => 6,
					    "height" => 3,
					    "weight" => 20
					  ),
					  "customs_info" => $customs_info
					));

					//return 'hello world';	
					//echo 'purchasing label...';

						
					//if($shipment->buy($shipment->lowest_rate(array('USPS'), array('First'))) == 'No rates found.');
					
					$easyPostCourierOptions = array('0' => array('carrier'=>'','service'=>'','deliveryDays'=>'','rate'=>''));
					$courierOptionCnt = 0;
					$rates = $shipment->rates;

					foreach($rates as $rate)
					{
 						//echo( $rate->carrier.' '.$rate->service.' '.$rate->rate.'  Devilery Days:  '.$rate->delivery_days .'</br>');

						$easyPostCourierOptions[$courierOptionCnt]['carrier'] = $rate->carrier;
						$easyPostCourierOptions[$courierOptionCnt]['service'] = $rate->service;
						$easyPostCourierOptions[$courierOptionCnt]['rate'] = $rate->rate;
						$easyPostCourierOptions[$courierOptionCnt]['deliveryDays'] = $rate->delivery_days;
						$courierOptionCnt++;

					}
					
					//var_dump($easyPostCourierOptions);
					//var_dump($shipment->rates);				
					//return ($shipment->postage_label->label_url);
					return($easyPostCourierOptions);

				}


			public function selShippingOption()
			{
				
			
				
				
			    $shipment = new Shipment;
				$shipment->order_id = Input::get('orderid');  
			    $shipment->user_id = Auth::id();
			    $shipment->shipment_type = 'domestic';
			    $shipment->carrier = Input::get('courierName');
			    $shipment->rate_id = '';
			    $shipment->cost = Input::get('shipRate');
			    $shipment->currency = 'INR';
			    $shipment->rate_label_url = '';
			    $shipment->additional_details = '';
			    //return ($ship->getMethods());
			    //$shipment->order_id;

			    
			    $shipment->save();


				return ($shipment);
			}

	
}
