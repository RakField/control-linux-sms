<?php
/*
Created by Tuukka Merilainen
http://www.tuukkamerilainen.com

Program allows you to control linux via sms. Send mail, get weather information
and run basic unix commands. For example you can get uptime info or reboot your
system if you can not access the Internet/your server.

Requirements:
 - registration to https://www.twilio.com/
 - purchased number (costs about 1$)
 - make this file reachabel for twilio (and enter url in twilio)

After message is sent to your twilio number it will pass it for this program
*/

//first 5 characters of the message are dedicated to declare the purpose (mail, ilma or exec)
$action = substr($_REQUEST['Body'],0,4);
//rest of the message is an actual content
$message = substr($_REQUEST['Body'],5,150);


if(strpos($message, "@") !== false && $action == "mail"){
	//To reach this point message have to be like: mail someone@example.com message-you-would-like-to-send
	$spacePos = strpos($message, " ");
	$mailTo = substr($message,0,$spacePos);
	$message = substr($message,$spacePos,100);
	shell_exec('echo '.$message.' | mail -s SMS-mail -a "From: mail@tuukkamerilainen.com" '.$mailTo.'');
	$result = "mail sent";
}else if($action == "ilma"){
	//To reach this point message have to be like: ilma helsinki (only works with Finnish cities/places)
	date_default_timezone_set('Europe/Helsinki');
	$pvm = date('Y-m-d');
	$time = date('H:00:00');
	$location = substr($_REQUEST['Body'],5,40);
	$dom = new DomDocument();
	//Takes data from ilmatieteenlaitos open-data
	//For this to work please add your api-key. You can get it for free from https://ilmatieteenlaitos.fi/rekisteroityminen-avoimen-datan-kayttajaksi
	$dom->loadXML(file_get_contents("http://data.fmi.fi/fmi-apikey/your-own-api-key/wfs?request=getFeature&storedquery_id=fmi::forecast::hirlam::surface::point::timevaluepair&place=".$location."&parameters=temperature&starttime=".$pvm."T".$time."Z&endtime=".$pvm."T".$time."Z"));
	$tempSource = $dom->getElementsByTagNameNS("http://www.opengis.net/wfs/2.0", "member");
	foreach ($tempSource as $m) {
        $point = $m->getElementsByTagNameNS("http://www.opengis.net/waterml/2.0", "point");
        foreach ($point as $p) {
                $tempTime =  $p->getElementsByTagName("time")->item(0)->nodeValue;
                $temperature = $p->getElementsByTagName("value")->item(0)->nodeValue;
        }
	$result = "Duck friendly temperature: ".$temperature." ".$location;
	}
}else if($action == "exec"){
	//To reach this point message have to be like: exec ls
	$result = shell_exec($message);
}else{
	$result = "command not found";
}

//Compiles xml data for clickatel. This is actually what clickatel is looking for.
header("content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>
<Response>
<Message><?php echo $result ?></Message>
</Response>

