<?php
//====================================================
//Performs the CURL Operation on the passed EndPoint
//====================================================
function doCurl($endPoint){
$curl = curl_init();
curl_setopt_array($curl, array(
  CURLOPT_URL => $endPoint,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_CUSTOMREQUEST => "GET",
  CURLOPT_SSL_VERIFYPEER => 0,
  CURLOPT_HTTPHEADER => array(
    "authorization: [Desk Auth Code]",
    "cache-control: no-cache"
  ),
));
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
//curl_setopt($process, CURLOPT_SSL_VERIFYPEER, FALSE);
$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);
if ($err) {
  echo "cURL Error #:" . $err;
}
//Decode the output
$decode = json_decode($response,true);
//$decode = $response;
return $decode;
}
//====================================================
//====================================================
$openCases = doCurl("https://idxbroker.desk.com/api/v2/cases/search?q=%20group%3A%22General%22%20status%3Aopen%20assigned%3A%22Drew%2CBecky%2CSasha%2CShannon%2CTyson%20Bishop%2CWill%22%20channel%3Aemail");
//====================================
//Getting The Agent List for General
//====================================
$userList = doCurl("https://idxbroker.desk.com/api/v2/users");
$supportUsers = array();
//Generate the Final User Array
//-----------------------------
function array_push_assoc($array, $key, $value){
$array[$key] = $value;
		return $array;
}
//Loop Through the user list
foreach($userList["_embedded"]["entries"] as $key => $userValue){
	
//Only keep users that are agents	
if ($userValue[level] == agent){
	$curlUrl = "https://idxbroker.desk.com".$userValue["_links"]["groups"]["href"];
	$groupMembership = doCurl($curlUrl);
//Only keep users that are in the General Group
foreach ($groupMembership["_embedded"]["entries"] as $key => $value){
	if ($value["name"] == "General"){
	
		//Create an array with the username and ID
		$supportUsers = array_push_assoc($supportUsers, $userValue["name"], str_replace("/api/v2/users/", "",$userValue["_links"]["self"]["href"]));
		
	}
}
}
}
//====================================
//Getting Agents With Cases
//====================================
$usersWithCases = array();
echo "<pre>";
foreach($openCases["_embedded"]["entries"] as $key => $value){
$curlUrl = "https://idxbroker.desk.com".$value["_links"]["assigned_user"]["href"];
$userName = doCurl($curlUrl);
	
if(!in_array("Client Onboarding",$value["labels"]) && !in_array("Spam",$value["labels"])){	

var_dump($value["labels"])."<p>";
	
array_push($usersWithCases,$userName["name"]);
}
}
echo "</pre>";
//====================================
//Agents and Number of Cases Per
//====================================
echo "<b><u>Open Cases Per Agent</u></b><p>";
foreach ($supportUsers as $usersKey=>$usersValue){
	
	echo "<b>".$usersKey.": </b>";
	
	$caseCounter = 0;
	foreach($usersWithCases as $withCasesKey=>$withCasesValue){
		
			if ($withCasesValue == $usersKey){
				$caseCounter++;
			}
	}
	echo "<b>".$caseCounter."</b><p>";
}
echo "<p>=================== Unassigned Cases ======================</p>";
$unassignedCases = doCurl("https://idxbroker.desk.com/api/v2/cases/search?q=%20group%3A%22General%22%20status%3Aopen%20assigned%3A%22unassigned%22%20channel%3Aemail");
$unassignedCaseCounter = 0;
foreach ($unassignedCases["_embedded"]["entries"] as $key=>$value){
	
	if (empty($value["labels"])){
	
	echo $value["id"]."<p>";
	$unassignedCaseCounter++;
}
}
echo "<b># of Unassigned: </b>".$unassignedCaseCounter."<p>";
?>
