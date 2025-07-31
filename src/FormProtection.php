<?php
namespace Dottyfix\FormControls;
use AltchaOrg\Altcha\ChallengeOptions;
use AltchaOrg\Altcha\Altcha;

class FormProtection {
	
	protected static function getAltaHmacKey() {
		return ALTCHA_HMAC_KEY;
	}
	
	public static function AltchaField($url) {return '<altcha-widget id="altcha" challengeurl="'.$url.'"></altcha-widget>';}
	
	public static function AltchaChallenge() {
		$altcha = new Altcha( self::getAltaHmacKey() );
		
		// Create a new challenge
		$options = new ChallengeOptions(
			maxNumber: 50000, // the maximum random number
			expires: (new \DateTimeImmutable())->add(new \DateInterval('PT10S')),
		);

		$challenge = $altcha->createChallenge($options);

		header('Content-Type: application/json; charset=utf-8');
		return json_encode($challenge);
	}
	
	public static function AltchaCheckValue($value) {
		$altcha = new Altcha( self::getAltaHmacKey() );
		$payload = json_decode(base64_decode($value), true);

		if ($altcha->verifySolution($payload, true))
			return true;
		return false;
	}
}
