<#1>
<?php
/**
 * Server Code Test DB Update Script
 *
 * @author Stefan Schweizer <schweizs@informatik.uni-freiburg.de>
 * @version $Id$
 */

$res = $ilDB->queryF("SELECT * FROM qpl_qst_type WHERE type_tag = %s", array('text'), array('assServerCodeQuestion'));

if ($res->numRows() == 0) 
{
    $res = $ilDB->query("SELECT MAX(question_type_id) maxid FROM qpl_qst_type");
    $data = $ilDB->fetchAssoc($res);
    $max = $data["maxid"] + 1;

    $affectedRows = $ilDB->manipulateF(
		"INSERT INTO qpl_qst_type (question_type_id, type_tag, plugin) VALUES (%s, %s, %s)",
		array("integer", "text", "integer"),
		array($max, 'assServerCodeQuestion', 1)
    );
}
	/*
	 * Add table for additional settings
	 *
	 */
    if(!$ilDB->tableExists('il_qpl_qst_srvcqst_dat'))
    {
		$fields = array(
			'question_fi' => array(
				'type' => 'integer',
				'length' => 4
			),

			'data' => array(
				'type' => 'clob'
			)
		);

		$ilDB->createTable("il_qpl_qst_srvcqst_dat", $fields);
		$ilDB->addPrimaryKey("il_qpl_qst_srvcqst_dat", array("question_fi"));

    }
?>