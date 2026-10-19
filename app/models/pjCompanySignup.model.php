<?php
if (!defined("ROOT_PATH"))
{
	header("HTTP/1.1 403 Forbidden");
	exit;
}
/**
 * Public (self) sign-up of a company: the step reached and the resume token.
 * One row per sign-up user. The token itself is never stored, only its SHA-256.
 */
class pjCompanySignupModel extends pjAppModel
{
	protected $primaryKey = 'id';

	protected $table = 'company_signups';

	protected $schema = array(
		array('name' => 'id', 'type' => 'int', 'default' => ':NULL'),
		array('name' => 'user_id', 'type' => 'int', 'default' => ':NULL'),
		array('name' => 'company_id', 'type' => 'int', 'default' => ':NULL'),
		array('name' => 'calendar_id', 'type' => 'int', 'default' => ':NULL'),
		array('name' => 'step', 'type' => 'tinyint', 'default' => '0'),
		array('name' => 'ai_generations', 'type' => 'smallint', 'default' => '0'),
		array('name' => 'status', 'type' => 'enum', 'default' => 'pending'),
		array('name' => 'token_hash', 'type' => 'varchar', 'default' => ':NULL'),
		array('name' => 'token_expires', 'type' => 'datetime', 'default' => ':NULL'),
		array('name' => 'ip', 'type' => 'varchar', 'default' => ':NULL'),
		array('name' => 'created', 'type' => 'datetime', 'default' => ':NOW()'),
		array('name' => 'modified', 'type' => 'datetime', 'default' => ':NULL'),
		array('name' => 'completed_at', 'type' => 'datetime', 'default' => ':NULL')
	);

	public static function factory($attr=array())
	{
		return new pjCompanySignupModel($attr);
	}
}
?>
