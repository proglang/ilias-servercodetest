<?php
require_once "./Modules/TestQuestionPool/classes/class.assQuestion.php";
require_once "./Modules/Test/classes/inc.AssessmentConstants.php";
require_once './Modules/TestQuestionPool/interfaces/interface.ilObjQuestionScoringAdjustable.php';
require_once './Modules/TestQuestionPool/interfaces/interface.ilObjAnswerScoringAdjustable.php';

require_once __DIR__ . "/util/CodeBlocks.php";

/**
 * Example class for question type plugins
 *
 * @author	Stefan Schweizer <schweizs@informatik.uni-freiburg.de>
 * @version	$Id:  $
 * Based on ilias-assCodeQuestions by Frank Bauer <frank.bauer@fau.de>
 * @ingroup ModulesTestQuestionPool
 */
class assServerCodeQuestion extends assQuestion implements ilObjQuestionScoringAdjustable, ilObjAnswerScoringAdjustable
{
	/**
	 * @var ilassServerCodeQuestionPlugin	The plugin object
	 */
	var $plugin = null;

	/* custom data we need to store fpr this question type. This array is serialized to json and stored in the db */
	var $additional_data = array();

	/**
	 * @var SCT/Languanges	The question object
	 */
	var $languages = NULL;

	/**
	 * Constructor
	 *
	 * The constructor takes possible arguments and creates an instance of the question object.
	 *
	 * @access public
	 * @see assQuestion:assQuestion()
	 */
	function __construct(
		$title = "",
		$comment = "",
		$author = "",
		$owner = -1,
		$question = ""

	) {
		// needed for excel export
		$this->getPlugin()->loadLanguageModule();
		parent::__construct($title, $comment, $author, $owner, $question);

		$this->plugin->includeClass("languages/languages.php");
		$this->languages = new \SCT\Languages();
	}

	function getLanguages()
	{
		return $this->languages;
	}
	function getLanguage()
	{
		$lng = $this->languages->get();
		$key = is_string($this->additional_data['language']) ? $this->additional_data['language'] : 'py';
		$obj = $lng[$key];
		return $obj ? $obj : array_values($this->languages->get())[0];
	}

	function setLanguage($newLanguage)
	{
		$this->additional_data['language'] = $newLanguage;
	}

	function getIsAllowRun()
	{
		return $this->additional_data['allowRun'] ?? true;
	}

	function setIsAllowRun($newValue)
	{
		$this->additional_data['allowRun'] = (bool) $newValue;
	}
	function getIsCalculatePoints(): bool
	{
		return $this->additional_data['calculatePoints'] ?? true;
	}
	function setIsCalculatePoints($newValue)
	{
		$this->additional_data['calculatePoints'] =  (bool) $newValue;
	}

	function getURL(): string
	{
		return $this->additional_data['url'] ?? '';
	}
	function setURL($url)
	{
		$this->additional_data['url'] = (string) $url;
	}

	function getToken()
	{
		return $this->additional_data['token'] ?? '';
	}
	function setToken($token)
	{
		$this->additional_data['token'] = (string) $token;
	}

	function getNumberOfBlocks()
	{
		if (is_array($this->additional_data['blocks'])) {
			return count($this->additional_data['blocks']);
		} else {
			return 3;
		}
	}
	function getBlock(int $id)
	{
		$block = new \SCT\CodeBlock();
		if (is_array($this->additional_data['blocks'])) {
			$block->fromArray($this->additional_data['blocks'][$id]);
		}
		return $block;
	}
	function clearBlocks()
	{
		$this->additional_data['blocks'] = array();
	}
	function setBlock($id, $block)
	{
		$this->additional_data['blocks'][$id] = $block->toArray();
	}

	function setJSONEncodedAdditionalData($data)
	{
		$this->additional_data = json_decode($data, true);
	}

	function getJSONEncodedAdditionalData()
	{
		return json_encode($this->additional_data);
	}

	/**
	 * Get the plugin object
	 *
	 * @return object The plugin object
	 */
	public function getPlugin()
	{
		if ($this->plugin == null) {
			include_once "./Services/Component/classes/class.ilPlugin.php";
			$this->plugin = ilPlugin::getPluginObject(IL_COMP_MODULE, "TestQuestionPool", "qst", "assServerCodeQuestion");
		}
		return $this->plugin;
	}

	/**
	 * Returns true, if the question is complete
	 *
	 * @return boolean True, if the question is complete for use, otherwise false
	 */
	public function isComplete()
	{
		// Please add here your own check for question completeness
		// The parent function will always return false
		if (($this->title) && ($this->author) && ($this->question) && ($this->getMaximumPoints() > 0)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Saves a question object to a database
	 * 
	 * @param	string		original id
	 * @access 	public
	 * @see assQuestion::saveToDb()
	 */
	function saveToDb($original_id = "")
	{
		global $ilDB;

		// save the basic data (implemented in parent)
		// a new question is created if the id is -1
		// afterwards the new id is set
		$this->saveQuestionDataToDb($original_id);
		$this->saveAdditionalQuestionDataToDb();
		$this->saveAnswerSpecificDataToDb();

		// save stuff like suggested solutions
		// update the question time stamp and completion status
		parent::saveToDb();
	}
	public function saveAdditionalQuestionDataToDb()
	{
		/** @var ilDBInterface $ilDB */
		global $ilDB;

		// Now you can save additional data
		// save data to DB
		$ilDB->replace(
			'il_qpl_qst_srvcqst_dat',
			array(
				'question_fi' => array('integer', $ilDB->quote($this->getId(), 'integer'))
			),
			array(
				'question_fi' => array('integer', $ilDB->quote($this->getId(), 'integer')),
				'data' => array('clob', $this->getJSONEncodedAdditionalData())
			)
		);
	}

	public function saveAnswerSpecificDataToDb()
	{
		/** @var ilDBInterface $ilDB */
		global $ilDB;
	}

	/**
	 * Loads a question object from a database
	 * This has to be done here (assQuestion does not load the basic data)!
	 *
	 * @param integer $question_id A unique key which defines the question in the database
	 * @see assQuestion::loadFromDb()
	 */
	public function loadFromDb($question_id)
	{
		global $ilDB;

		// load the basic question data
		$result = $ilDB->query("SELECT qpl_questions.* FROM qpl_questions WHERE question_id = "
			. $ilDB->quote($question_id, 'integer'));

		$data = $ilDB->fetchAssoc($result);
		$this->setId($question_id);
		$this->setTitle($data["title"]);
		$this->setComment($data["description"]);
		$this->setSuggestedSolution($data["solution_hint"]);
		$this->setOriginalId($data["original_id"]);
		$this->setObjId($data["obj_fi"]);
		$this->setAuthor($data["author"]);
		$this->setOwner($data["owner"]);
		$this->setPoints($data["points"]);

		include_once("./Services/RTE/classes/class.ilRTE.php");
		$this->setQuestion(ilRTE::_replaceMediaObjectImageSrc($data["question_text"], 1));
		$this->setEstimatedWorkingTime(substr($data["working_time"], 0, 2), substr($data["working_time"], 3, 2), substr($data["working_time"], 6, 2));

		// now you can load additional data
		$result = $ilDB->query(
			"SELECT d.data FROM il_qpl_qst_srvcqst_dat d"
				. " WHERE d.question_fi ="
				. $ilDB->quote($question_id, 'integer')
		);

		$data = $ilDB->fetchAssoc($result);
		$this->setJSONEncodedAdditionalData($data["data"]);

		try {
			$this->setAdditionalContentEditingMode($data['add_cont_edit_mode']);
		} catch (ilTestQuestionPoolException $e) {
		}

		// loads additional stuff like suggested solutions
		parent::loadFromDb($question_id);
	}


	/**
	 * Duplicates a question
	 * This is used for copying a question to a test
	 *
	 * @access public
	 */
	function duplicate($for_test = true, $title = "", $author = "", $owner = "", $testObjId = null)
	{
		if ($this->getId() <= 0) {
			// The question has not been saved. It cannot be duplicated
			return;
		}

		// make a real clone to keep the object unchanged
		$clone = clone $this;

		$original_id = assQuestion::_getOriginalId($this->getId());
		$clone->setId(-1);

		if ((int) $testObjId > 0) {
			$clone->setObjId($testObjId);
		}

		if ($title) {
			$clone->setTitle($title);
		}
		if ($author) {
			$clone->setAuthor($author);
		}
		if ($owner) {
			$clone->setOwner($owner);
		}

		if ($for_test) {
			$clone->saveToDb($original_id, false);
		} else {
			$clone->saveToDb('', false);
		}

		// copy question page content
		$clone->copyPageOfQuestion($this->getId());
		// copy XHTML media objects
		$clone->copyXHTMLMediaObjectsOfQuestion($this->getId());

		// call the event handler for duplication
		$clone->onDuplicate($this->getObjId(), $this->getId(), $clone->getObjId(), $clone->getId());

		return $clone->getId();
	}

	/**
	 * Copies a question
	 * This is used when a question is copied on a question pool
	 *
	 * @access public
	 */
	function copyObject($target_questionpool_id, $title = "")
	{
		if ($this->getId() <= 0) {
			// The question has not been saved. It cannot be duplicated
			return;
		}

		// make a real clone to keep the object unchanged
		$clone = clone $this;

		$original_id = assQuestion::_getOriginalId($this->getId());
		$source_questionpool_id = $this->getObjId();
		$clone->setId(-1);
		$clone->setObjId($target_questionpool_id);
		if ($title) {
			$clone->setTitle($title);
		}

		// save the clone data
		$clone->saveToDb('', false);

		// copy question page content
		$clone->copyPageOfQuestion($original_id);
		// copy XHTML media objects
		$clone->copyXHTMLMediaObjectsOfQuestion($original_id);

		// call the event handler for copy
		$clone->onCopy($source_questionpool_id, $original_id, $clone->getObjId(), $clone->getId());

		return $clone->getId();
	}



	/**
	 * Get a submitted solution array from $_POST
	 *
	 * In general this may return any type that can be stored in a php session
	 * The return value is used by:
	 * 		savePreviewData()
	 * 		saveWorkingData()
	 * 		calculateReachedPointsForSolution()
	 *
	 * @return	array	('value1' => string)
	 */
	protected function getSolutionSubmit()
	{
		$data = $_POST['block'][$this->getId()];
		$result = array();
		for ($i = 0; $i < $this->getNumberOfBlocks(); $i++) {
			$block = $this->getBlock($i);
			if ($block->getType() == \SCT\CodeBlockType::SolutionCode) {
				$result[$i] = $data[$i];
			}
		}
		$result["points"] = $_POST['points'][$this->getId()];;
		return array(
			'value1' => json_encode($result),
			'value2' => '',
		);
	}
	public function getLastSolution($active_id, $pass = null, $authorized = true)
	{
		if (is_null($pass)) {
			$pass = $this->getSolutionMaxPass($active_id);
		}

		// other calls should explictly indicate whether to use the authorized or intermediate solutions
		$solutions = $this->getSolutionValues($active_id, $pass, $authorized);
		$solution = empty($solutions) ? array() : end($solutions); // get last solution

		$value1 = $solution["value1"] ?? '{}';
		$value2 = $solution["value2"] ?? '{}';
		return array('value1' => json_decode($value1, true), 'value2' => json_decode($value2, true));
	}
	/**
	 * Calculate the reached points from a solution array
	 *
	 * @param	array	('value1' => string)
	 * @return  float	reached points
	 */
	protected function calculateReachedPointsForSolution($solution)
	{
		$points = 0;
		if ($this->getIsCalculatePoints()) {
			$points = $solution["value1"]["points"];
			if (empty($points) || $points < 0) {
				$points = 0;
			}
			if ($points > $this->getMaximumPoints()) {
				$points = $this->getMaximumPoints();
			}
		}

		// return the raw points given to the answer
		// these points will afterwards be adjusted by the scoring options of a test
		return $points;
	}



	/**
	 * Returns the points, a learner has reached answering the question
	 * The points are calculated from the given answers.
	 *
	 * @param integer $active 	The Id of the active learner
	 * @param integer $pass 	The Id of the test pass
	 * @param boolean $returndetails (deprecated !!)
	 * @return integer/array $points/$details (array $details is deprecated !!)
	 * @access public
	 * @see  assQuestion::calculateReachedPoints()
	 */
	function calculateReachedPoints($active_id, $pass = NULL, $authorizedSolution = true, $returndetails = FALSE)
	{
		if ($returndetails) {
			throw new ilTestException('return details not implemented for ' . __METHOD__);
		}
		if (is_null($pass)) {
			$pass = $this->getSolutionMaxPass($active_id);
		}

		// get the answers of the learner from the tst_solution table
		// the data is saved by saveWorkingData() in this class
		$solution = $this->getLastSolution($active_id, $pass);

		// there may be more solutions stored due to race conditions
		// the last saved solution record wins
		return $this->calculateReachedPointsForSolution($solution);
	}

	/**
	 * Sets the points, a learner has reached answering the question
	 *
	 * @param integer $user_id The database ID of the learner
	 * @param integer $test_id The database Id of the test containing the question
	 * @param integer $points The points the user has reached answering the question
	 * @return boolean true on success, otherwise false
	 * @access public
	 */
	function setReachedPoints($active_id, $points, $pass = NULL)
	{
		global $ilDB;

		if (($points > 0) && ($points <= $this->getPoints())) {
			if (is_null($pass)) {
				$pass = $this->getSolutionMaxPass($active_id);
			}
			$affectedRows = $ilDB->manipulateF(
				"UPDATE tst_test_result SET points = %s WHERE active_fi = %s AND question_fi = %s AND pass = %s",
				array('float', 'integer', 'integer', 'integer'),
				array($points, $active_id, $this->getId(), $pass)
			);
			self::_updateTestPassResults($active_id, $pass);
			return TRUE;
		} else {
			return TRUE;
		}
	}

	private function saveWorkingDataInner($solution, $active_id, $pass, $authorized, $value1, $value2)
	{
		global $ilDB;
		global $ilUser;
		// save the answers of the learner to tst_solution table
		// this data is question type specific
		// it is used used by calculateReachedPointsForSolution() in this class

		$result = $ilDB->queryF(
			"SELECT solution_id FROM tst_solutions WHERE active_fi = %s AND question_fi = %s AND pass = %s",
			array('integer', 'integer', 'integer'),
			array($active_id, $this->getId(), $pass)
		);

		$row = $ilDB->fetchAssoc($result);
		if ($row) {
			$affectedRows = $ilDB->update(
				"tst_solutions",
				array(
					"active_fi"   => array("integer", $active_id),
					"question_fi" => array("integer", $this->getId()),
					"pass"        => array("integer", $pass),
					"tstamp"      => array("integer", time()),

					// points, value1 and value2 are multi-purpose fields
					// store here what you want from the POST data
					// in our example we allow to enter these values directly
					"value1"      => array("clob", $solution["value1"]),
					"value2"      => array("clob", $solution["value2"]),
				),
				array(
					"solution_id" => array("integer", $row['solution_id']),
				)
			);
		} else {
			$next_id = $ilDB->nextId('tst_solutions');
			$affectedRows = $ilDB->insert(
				"tst_solutions",
				array(
					"solution_id" => array("integer", $next_id),
					"active_fi"   => array("integer", $active_id),
					"question_fi" => array("integer", $this->getId()),
					"pass"        => array("integer", $pass),
					"tstamp"      => array("integer", time()),

					// points, value1 and value2 are multi-purpose fields
					// store here what you want from the POST data
					// in our example we allow to enter these values directly
					"value1"      => array("clob", $solution["value1"]),
					"value2"      => array("clob", $solution["value2"]),
				)
			);
		};
	}

	/**
	 * Saves the learners input of the question to the database
	 *
	 * @param 	integer $test_id The database id of the test containing this question
	 * @return 	boolean Indicates the save status (true if saved successful, false otherwise)
	 * @access 	public
	 * @see 	assQuestion::saveWorkingData()
	 */
	function saveWorkingData($active_id, $pass = NULL, $authorized = true)
	{
		global $ilDB;
		global $ilUser;

		if (is_null($pass)) {
			include_once "./Modules/Test/classes/class.ilObjTest.php";
			$pass = ilObjTest::_getPass($active_id);
		}

		// get the submitted solution
		$solution = $this->getSolutionSubmit();


		if (method_exists($this->getProcessLocker(), 'executeUserSolutionUpdateLockOperation')) { //ilias 5.2
			$this->getProcessLocker()->executeUserSolutionUpdateLockOperation(function () use ($solution, $active_id, $pass, $authorized, $value1, $value2) {
				$this->saveWorkingDataInner($solution, $active_id, $pass, $authorized, $value1, $value2);
			});
		} else { // ilias 5.1
			// lock to prevent race conditions
			$this->getProcessLocker()->requestUserSolutionUpdateLock();

			$this->saveWorkingDataInner($solution, $active_id, $pass, $authorized, $value1, $value2);

			// unlock
			$this->getProcessLocker()->releaseUserSolutionUpdateLock();
		}


		// Check if the user has entered something
		// Then set entered_values accordingly
		if (!empty($solution["points"])) {
			$entered_values = TRUE;
		}

		if ($entered_values) {
			include_once("./Modules/Test/classes/class.ilObjAssessmentFolder.php");
			if (ilObjAssessmentFolder::_enabledAssessmentLogging()) {
				$this->logAction($this->lng->txtlng("assessment", "log_user_entered_values", ilObjAssessmentFolder::_getLogLanguage()), $active_id, $this->getId());
			}
		} else {
			include_once("./Modules/Test/classes/class.ilObjAssessmentFolder.php");
			if (ilObjAssessmentFolder::_enabledAssessmentLogging()) {
				$this->logAction($this->lng->txtlng("assessment", "log_user_not_entered_values", ilObjAssessmentFolder::_getLogLanguage()), $active_id, $this->getId());
			}
		}

		return true;
	}


	/**
	 * Reworks the allready saved working data if neccessary
	 *
	 * @access protected
	 * @param integer $active_id
	 * @param integer $pass
	 * @param boolean $obligationsAnswered
	 */
	/*protected function reworkWorkingData($active_id, $pass, $obligationsAnswered, $authorized)
	{
		// normally nothing needs to be reworked
	}*/
	/**
	 * Returns the question type of the question
	 *
	 * @return string The question type of the question
	 */
	public function getQuestionType()
	{
		return "assServerCodeQuestion";
	}

	/**
	 * Returns the names of the additional question data tables
	 *
	 * all tables must have a 'question_fi' column
	 * data from these tables will be deleted if a question is deleted
	 *
	 * @return mixed 	the name(s) of the additional tables (array or string)
	 */
	public function getAdditionalTableName()
	{
		return array('il_qpl_qst_srvcqst_dat');
	}


	/**
	 * Creates an Excel worksheet for the detailed cumulated results of this question
	 *
	 * @access public
	 * @see assQuestion::setExportDetailsXLS()
	 */
	public function setExportDetailsXLS($worksheet, $startrow, $active_id, $pass, &$format_title = '', &$format_bold = '')
	{
		global $lng;

		$il52 = file_exists('./Modules/TestQuestionPool/classes/class.ilAssExcelFormatHelper.php');
		if (!$il52) {
			include_once("./Services/Excel/classes/class.ilExcelUtils.php");
		} else {
			include_once './Modules/TestQuestionPool/classes/class.ilAssExcelFormatHelper.php';
		}


		$solution = $this->getLastSolution($active_id, $pass);
		$value1 = $solution["value1"] ?? "";
		$value2 = $solution["value2"] ?? "";

		if ($il52) {
			// also see parent::setExportDetailsXLS($worksheet, $startrow, $active_id, $pass);
			$worksheet->setFormattedExcelTitle($worksheet->getColumnCoord(0) . $startrow, $this->plugin->txt($this->getQuestionType()));
			$worksheet->setFormattedExcelTitle($worksheet->getColumnCoord(1) . $startrow, $this->getTitle());
		} else {
			$worksheet->writeString($startrow, 0, ilExcelUtils::_convert_text($this->plugin->txt($this->getQuestionType())), $format_title);
			$worksheet->writeString($startrow, 1, ilExcelUtils::_convert_text($this->getTitle()), $format_title);
		}
		$i = 1;

		// now provide a result string and write it to excel
		// it is also possible to write multiple rows
		if ($il52) {
			$stringEscaping = $worksheet->getStringEscaping();
			$worksheet->setStringEscaping(false);
			$worksheet->setCell($startrow + $i, 0, $this->plugin->txt("label_value1"));
			$worksheet->setCell($startrow + $i, 1, $value1);
			$worksheet->setStringEscaping($stringEscaping);
		} else {
			$worksheet->writeString($startrow + $i, 0, ilExcelUtils::_convert_text($this->plugin->txt("label_value1")), $format_bold);
			$worksheet->write($startrow + $i, 1, ilExcelUtils::_convert_text($value1));
		}
		$i++;

		if ($il52) {
			$worksheet->setCell($startrow + $i, 0, $this->plugin->txt("label_value2"));
			$worksheet->setCell($startrow + $i, 1, $value2);
		} else {
			$worksheet->writeString($startrow + $i, 0, ilExcelUtils::_convert_text($this->plugin->txt("label_value2")), $format_bold);
			$worksheet->write($startrow + $i, 1, ilExcelUtils::_convert_text($value2));
		}

		if ($il52) {
			$worksheet->setCell($startrow + $i, 0, $this->plugin->txt("label_points"));
			$worksheet->setCell($startrow + $i, 1, $points);
		} else {

			// intelephense: disable=1009
			$worksheet->writeString($startrow + $i, 0, ilExcelUtils::_convert_text($this->plugin->txt("label_points")), $format_bold);
			$worksheet->write($startrow + $i, 1, ilExcelUtils::_convert_text($points));
		}
		$i++;
		return $startrow + $i + 1;
	}

	/**
	 * Creates a question from a QTI file
	 * Receives parameters from a QTI parser and creates a valid ILIAS question object
	 * Extension needed to get the plugin path for the import class
	 *
	 * @access public
	 * @see assQuestion::fromXML()
	 */
	function fromXML(&$item, &$questionpool_id, &$tst_id, &$tst_object, &$question_counter, &$import_mapping)
	{
		$this->getPlugin()->includeClass("import/qti12/class.assServerCodeQuestionImport.php");
		$import = new assServerCodeQuestionImport($this);
		$import->fromXML($item, $questionpool_id, $tst_id, $tst_object, $question_counter, $import_mapping);
	}

	/**
	 * Returns a QTI xml representation of the question and sets the internal
	 * domxml variable with the DOM XML representation of the QTI xml representation
	 * Extension needed to get the plugin path for the import class
	 *
	 * @return string The QTI xml representation of the question
	 * @access public
	 * @see assQuestion::toXML()
	 */
	function toXML($a_include_header = true, $a_include_binary = true, $a_shuffle = false, $test_output = false, $force_image_references = false)
	{
		$this->getPlugin()->includeClass("export/qti12/class.assServerCodeQuestionExport.php");
		$export = new assServerCodeQuestionExport($this);
		return $export->toXML($a_include_header, $a_include_binary, $a_shuffle, $test_output, $force_image_references);
	}

	/**
	 * returns boolean wether it is possible to set
	 * this question type as obligatory or not
	 * considering the current question configuration
	 *
	 * (overwrites method in class assQuestion)
	 *
	 * @param integer $questionId
	 * @return boolean $obligationPossible
	 */
	public static function isObligationPossible($questionId)
	{
		return true;
	}
}
