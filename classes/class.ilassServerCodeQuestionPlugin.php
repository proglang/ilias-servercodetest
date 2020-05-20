<?php

include_once "./Modules/TestQuestionPool/classes/class.ilQuestionsPlugin.php";
	
/**
* Question plugin Example
*
 * @author	Stefan Schweizer<schweizs@informatik.uni-freiburg.de>
 * @version	$Id:  $
 * Based on ilias-assCodeQuestions by Frank Bauer <frank.bauer@fau.de>
* @ingroup ModulesTestQuestionPool
*/
class ilassServerCodeQuestionPlugin extends ilQuestionsPlugin
{
		final function getPluginName()
		{
			return "assServerCodeQuestion";
		}
		
		final function getQuestionType()
		{
			return "assServerCodeQuestion";
		}
		final function getQuestionTypeTranslation()
		{
			return $this->txt($this->getQuestionType());
		}

}
