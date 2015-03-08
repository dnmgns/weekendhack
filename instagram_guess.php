<?php
$correctAnswer = '35';

#https://instagram.com/oauth/authorize/?client_id=x&redirect_uri=http://localhost:8080&response_type=token
$accessToken = 'x';

$recentMediaLink = 'https://api.instagram.com/v1/users/self/media/recent?access_token='.$accessToken;

$recentMedia = file_get_contents($recentMediaLink);
$mediaArr = json_decode($recentMedia, true);

$mediaId = $mediaArr['data'][0]['id'];

$logfile = fopen($mediaId.'.log', "a+");
$commentIDs = explode(PHP_EOL, file_get_contents($mediaId.'.log'));

date_default_timezone_set('Europe/Helsinki');

print('Executed script '.   date("Y/m/d h:i:sa") . PHP_EOL);

$mediaCommentsLink = 'https://api.instagram.com/v1/media/'.$mediaId.'/comments?access_token='.$accessToken;

$recentComment = file_get_contents($mediaCommentsLink);
$recentCommentArr = json_decode($recentComment, true);

$comments = $recentCommentArr['data'];

$winner = false;
if ( file_exists($mediaId.'.lock')) {
	$winner = file_get_contents($mediaId.'.lock');
} 	
# save the $comment['id'], skip check if already processed
foreach($comments as $comment) {

	// om dnmgns
	if($comment['from']['username'] == 'dnmgns') { continue; }

	// Om inte redan kommenterat
	// if exists mediaID.lock, bail bail!

	if ( ! in_array($comment['id'], $commentIDs )){
		fwrite($logfile, $comment['id'] . PHP_EOL);

		if ($winner) {
			// Svara alla att de har förlorat
			echo 'We already have a winner but got answer from '. $comment['from']['username'] . PHP_EOL;
			comment(false, $comment['from']['username'], true);

		} else {
			$words = explode(' ', strtolower($comment['text']));
			if (in_array($correctAnswer1, $words)) {
				$match = true;
				$winner = true;
				echo 'We got a WINNER! '. $comment['from']['username'] . PHP_EOL;
				// Skapa lockfil.
				$lockfile = fopen($mediaId.'.lock', 'a+');
				fwrite($lockfile, $comment['from']['username']);
				fclose($lockfile);
				comment(true, $comment['from']['username']);
			} else {
				echo 'Wrong answer from '. $comment['from']['username'] . PHP_EOL;
				comment(false, $comment['from']['username']);
			}
		}
	}
}

fclose($logfile);

function comment($winner, $username, $alreadyGotWinner=false) {
	global $accessToken;
	global $mediaId;
	if($winner) {
		$text = 'We have a winner! Congratulations to @' .$username;
	} else {
		if($alreadyGotWinner == true)
		{
			$text = 'We already got a winner, better luck next time @' . $username;
		}
		else
		{
			$text = 'That\'s not the right answer, try again @' . $username;
		}
		
	}
	$postUrl = 'https://api.instagram.com/v1/media/'.$mediaId.'/comments';
	$fields = array(
		'access_token' => $accessToken,
		'text' => $text
	);

	$ch = curl_init();
	print($postUrl);
	curl_setopt($ch,CURLOPT_URL, $postUrl);
	curl_setopt($ch,CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 

	$ips = '212.116.90.2';
	$secret = 'da5d2eb619c54c1298c27e06252a4ce7';
	$signature = (hash_hmac('sha256', $ips, $secret, false));
	$header = join('|', array($ips, $signature));

	curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-Insta-Forwarded-For: $header"));

	//url-ify the data for the POST
	foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
	rtrim($fields_string, '&');

	curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);

	$result = curl_exec($ch);

	var_dump($result);

}