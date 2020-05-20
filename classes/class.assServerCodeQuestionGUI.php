<?php
require_once "./Modules/TestQuestionPool/classes/class.assQuestionGUI.php";
require_once "./Modules/Test/classes/inc.AssessmentConstants.php";
require_once './Modules/TestQuestionPool/interfaces/interface.ilGuiQuestionScoringAdjustable.php';
require_once './Modules/TestQuestionPool/interfaces/interface.ilGuiAnswerScoringAdjustable.php';
require_once __DIR__ . "/util/CodeBlocks.php";

abstract class SCT_VIEW_TYPE
{
	const EDIT = 0;
	const TEST = 1;
	const PREVIEW = 2;
	const SOLUTION = 3;
	const MANUAL_SCORE = 4;
};

/**
 * Example GUI class for question type plugins
 *
 * @author	Stefan Schweizer <schweizs@informatik.uni-freiburg.de>
 * @version	$Id:  $
 * Based on ilias-assCodeQuestions by Frank Bauer <frank.bauer@fau.de>
 * @ingroup ModulesTestQuestionPool
 *
 * @ilctrl_iscalledby assServerCodeQuestionGUI: ilObjQuestionPoolGUI, ilObjTestGUI, ilQuestionEditGUI, ilTestExpressPageObjectGUI
 * @ilCtrl_Calls assServerCodeQuestionGUI: ilFormPropertyDispatchGUI
 */
class assServerCodeQuestionGUI extends assQuestionGUI implements ilGuiQuestionScoringAdjustable, ilGuiAnswerScoringAdjustable
{
	/**
	 * @const	string	URL base path for including special javascript and css files
	 */
	const URL_PATH = "./Customizing/global/plugins/Modules/TestQuestionPool/Questions/assServerCodeQuestion";

	/**
	 * @var ilassServerCodeQuestionPlugin	The plugin object
	 */
	var $plugin = NULL;


	/**
	 * @var assServerCodeQuestion	The question object
	 */
	var $object = NULL;

	/**
	 * Constructor
	 *
	 * @param integer $id The database id of a question object
	 * @access public
	 */
	public function __construct($id = -1)
	{
		parent::__construct();
		include_once "./Services/Component/classes/class.ilPlugin.php";
		$this->plugin = ilPlugin::getPluginObject(IL_COMP_MODULE, "TestQuestionPool", "qst", "assServerCodeQuestion");
		$this->plugin->includeClass("class.assServerCodeQuestion.php");
		$this->object = new assServerCodeQuestion();
		if ($id >= 0) {
			$this->object->loadFromDb($id);
		}
	}

	private function formatTextOutput(string $text):string {
		$language = $this->object->getLanguage()->getName();
		$code_tags = [
			"code"=>false,
			"hl"=>true,
		];
		$text = htmlentities($text);
		foreach ($code_tags as $tag=>$inline) {
			$style = "display: block";
			if ($inline) {
				$style = "display: inline";
			}
			$text = str_replace("[$tag]", "<code style=\"$style\">", $text);
			$text = str_replace("[/$tag]", "</code>", $text);
		}
		return "<div style=\"white-space: pre ; display: block; unicode-bidi: embed\">$text</div>";
	}

	private function prepareTemplate($template, $view_type, $id=null)
	{
		$id = $id??$this->object->getId();
		$language = $this->object->getLanguage();
		$template->addCss(self::URL_PATH . '/css/custom.css');
		$template->addCss(self::URL_PATH . '/js/codemirror/lib/codemirror.css');
		$template->addCss(self::URL_PATH . '/js/codemirror/theme/solarized.css');

		$template->addCss(self::URL_PATH . '/js/highlight.js/styles/github.css');
		$template->addJavascript(self::URL_PATH . '/js/highlight.js/highlight.pack.js');

		$template->addJavascript(self::URL_PATH . '/js/codemirror/lib/codemirror.js');
		$template->addJavascript(self::URL_PATH . '/js/codemirror/addon/edit/closebrackets.js');

		$template->addJavascript(self::URL_PATH . '/js/helper.js');
		$template->addJavascript(self::URL_PATH . '/js/exec.js');
		$template->addJavascript(self::URL_PATH . '/js/Base64.js');
		$template->addJavascript(self::URL_PATH . '/js/edit.js');
		$template->addJavascript(self::URL_PATH . '/js/view.js');

		$template->addOnLoadCode('SCTCodeblock(' . $id . ', "' . $language->getMIME() . '");');
		if ($this->object->getIsAllowRun() || SCT_VIEW_TYPE::EDIT) {
			$template->addOnLoadCode('SCTExec(' . $id . ',"' . $this->object->getURL() . '","' . $this->object->getToken() . '");');
		}
		if ($view_type == SCT_VIEW_TYPE::EDIT) {
			$template->addOnLoadCode("registerLanguages(" . json_encode($this->object->getLanguages()->getMIME()) . ")");
			$template->addOnLoadCode("sct_init_edit()");
			foreach ($this->object->getLanguages()->get() as $lang) {
				$template->addJavascript(self::URL_PATH . '/js/codemirror/mode/' . $lang->getCodeMirrorFile());
			}
		} else {
			$template->addJavascript(self::URL_PATH . '/js/codemirror/mode/' . $language->getCodeMirrorFile());
		}
		if ($view_type == SCT_VIEW_TYPE::MANUAL_SCORE) {
			$template->addOnLoadCode('$("td, table, tr, tbody").css("width","inherit").css("display","block");');
		}
		$template->addOnLoadCode("$('code').each(function(i, block) {hljs.highlightBlock(block);});");
	}

	private function createRunHTMLCode($questionID, $force = False)
	{
		$runCode = "";
		if ($this->object->getIsAllowRun() || $force) {
			$tpl = $this->plugin->getTemplate('tpl.il_as_qpl_srvcodeqst_run_code.html');
			$tpl->setVariable("RUN_LABEL", $this->plugin->txt('run_code'));
			$tpl->setVariable("QID", $questionID);
			$tpl->setVariable("URL", $this->object->getURL());
			$tpl->setVariable("TOKEN", $this->object->getToken());
			$tpl->setVariable("FN", "");

			$runCode .= $tpl->get();
		}
		return $runCode;
	}

	private function createTextArea(bool $edit, int $index, $text = null, $id=null, bool $is_test=false)
	{
		$id = $id??$this->object->getId();
		$block = $this->object->getBlock($index);
		$type = $block->getType();
		$code = $block->getContent();
		if (!$edit && !$block->getSend()) return "";
		if ($type == \SCT\CodeBlockType::Text && !$edit) {
			return $this->formatTextOutput($code);
		}
		$tpl = $this->plugin->getTemplate('tpl.il_as_qpl_srvcodeqst_edit_code.html');

		$tpl->setVariable("QID", $id);
		$tpl->setVariable("BLOCK_ID", $index == -1 ? '[ID]' : $index);
		$tpl->setVariable("SHOW_LINES", $block->getLines());
		$tpl->setVariable("BLOCK_TYPE", $type);

		$attributes = [];
		$classes = [];
		if ($edit) {
			if ($index == -1) {
				$attributes[] = "data-ignore";
			}
		} else {
			switch ($type) {
				case \SCT\CodeBlockType::TestCode:
					$attributes[] = "data-test-code";
				case \SCT\CodeBlockType::HiddenCode:
					$classes[] = "sct_hidden";
					$attributes[] = "data-encoded";
					$code = base64_encode($code);
					break;
				case \SCT\CodeBlockType::StaticCode:
					$attributes[] = "data-readonly";
					break;
				case \SCT\CodeBlockType::SolutionCode:
					if ($is_test) {
						$code = $text ?? "";
					} else {
						$code = $text ?? $code;
					}
					break;
			}
		}
		$tpl->setVariable("ADDITIONAL_ATTRIBUTES", implode(" ", $attributes));
		$tpl->setVariable("ADDITIONAL_CLASSES", implode(" ", $classes));
		$tpl->setVariable("CONTENT", $code);

		return $tpl->get();
	}
	/**
	 * Get the html output of the question for different usages (preview, test)
	 *
	 * @param    array            values of the user's solution
	 *
	 * @see assAccountingQuestion::getSolutionSubmit()
	 */
	private function getQuestionOutput($value1, $value2, $view_type, $show_question_text = true, $show_correct_solution = false)
	{
		if ($view_type == SCT_VIEW_TYPE::EDIT) {
			$show_correct_solution = true;
		}
		$id = $this->object->getId();
		if ($show_correct_solution) {
			$id = -$id;
		}
		$this->prepareTemplate($this->tpl, $view_type, $id);


		//we can not run when we have multiple instances of the same question on screen
		$template = $this->plugin->getTemplate("tpl.il_as_qpl_srvcodeqst_output.html");

		if ($show_question_text) {
			$questiontext = $this->object->getQuestion();
			//$questiontext = $this->object->prepareTextareaOutput($questiontext, TRUE);
			$questiontext = $this->formatTextOutput($questiontext);
			$template->setVariable("QUESTIONTEXT", $questiontext);
		}


		$html = '';
		for ($i = 0; $i < $this->object->getNumberOfBlocks(); $i++) {
			$html .= $this->createTextArea(false, $i, $show_correct_solution ? null : ($value1[$i]), $id, $view_type == SCT_VIEW_TYPE::TEST || $view_type == SCT_VIEW_TYPE::PREVIEW);
		}
		$template->setVariable("BLOCK_HTML", $html);
		$runCode = $this->createRunHTMLCode($id);
		$template->setVariable("RUN_CODE_HTML", $runCode);

		if ($view_type == SCT_VIEW_TYPE::TEST || $view_type == SCT_VIEW_TYPE::PREVIEW) {
			if ($this->object->getIsCalculatePoints()) {
				$tpl = $this->plugin->getTemplate('tpl.il_as_qpl_srvcodeqst_test_point.html');
				$tpl->setVariable("QID", $id);
				$template->setVariable("POINTS", $tpl->get());
			}
		}

		$template->setVariable("QID", $id);
		$template->setVariable("LABEL_VALUE1", $this->plugin->txt('label_value1'));

		$questionoutput = $template->get();
		return $questionoutput;
	}


	public function createBlockTypeOption($value, $nameRef, $currentValue = -1)
	{
		return '<option value="' . $value . '" ' . ($currentValue == $value ? 'selected' : '') . '>' . $this->plugin->txt($nameRef) . '</option>';
	}
	public function createCodeBlockInput($i)
	{
		$block = $this->object->getBlock($i);
		$type = $block->getType();
		$options_html  = $this->createBlockTypeOption(\SCT\CodeBlockType::Text, 'plain_text', $type);
		$options_html .= $this->createBlockTypeOption(\SCT\CodeBlockType::StaticCode, 'static_code', $type);
		$options_html .= $this->createBlockTypeOption(\SCT\CodeBlockType::HiddenCode, 'hidden_code', $type);
		$options_html .= $this->createBlockTypeOption(\SCT\CodeBlockType::SolutionCode, 'solution_code', $type);
		$options_html .= $this->createBlockTypeOption(\SCT\CodeBlockType::TestCode, 'test_code', $type);

		$tpl = $this->plugin->getTemplate('tpl.il_as_qpl_srvcodeqst_edit_block_ui.html');
		$tpl->setVariable("QID", $this->object->getId());
		$tpl->setVariable("BLOCK_ID", $i == -1 ? '[ID]' : $i);
		$tpl->setVariable("REMOVE_TXT", $this->plugin->txt('remove'));
		$tpl->setVariable("BLOCK_TYPE_OPTIONS", $options_html);
		$tpl->setVariable("CODE_EDITOR", $this->createTextArea(true, $i));


		$tpl->setVariable("BLOCK_SEND_TXT", $this->plugin->txt('block_send_label'));
		$tpl->setVariable("BLOCK_SEND", $block->getSend() ? "checked" : "");

		$tpl->setVariable("SHOW_LINES_TXT", $this->plugin->txt('show_lines_label'));
		$tpl->setVariable("SHOW_LINES", $block->getLines());

		return $tpl->get();
	}


	//! ---------------------------------------------------------------------
	//! -------------------------- assQuestionGUI ---------------------------
	//! ---------------------------------------------------------------------

	/**
	 * Creates an output of the edit form for the question
	 * SCT_VIEW_TYPE::EDIT
	 *
	 * defined in assQuestionGUI
	 * 
	 * @param bool $checkonly
	 * @return bool
	 */
	public function editQuestion($checkonly = FALSE)
	{
		include_once "./Services/AdvancedEditing/classes/class.ilObjAdvancedEditing.php";

		$save = $this->isSaveCommand();
		$this->getQuestionTemplate();
		$this->prepareTemplate($this->tpl, SCT_VIEW_TYPE::EDIT);

		include_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();
		$form->setFormAction($this->ctrl->getFormAction($this));
		$form->setTitle($this->outQuestionType());
		$form->setMultipart(TRUE);
		$form->setTableWidth("100%");
		$form->setId("srvcodeqst");
		$form->setDescription($this->plugin->txt('question_edit_info'));
		$this->addBasicQuestionFormProperties($form);
		$this->populateQuestionSpecificFormPart($form);

		// Here you can add question type specific form properties
		$this->populateTaxonomyFormSection($form);
		$this->addQuestionFormCommandButtons($form);

		$errors = false;

		if ($save) {
			$form->setValuesByPost();
			$errors = !$form->checkInput();
			$form->setValuesByPost(); // again, because checkInput now performs the whole stripSlashes handling and we need this if we don't want to have duplication of backslashes
			if ($errors) $checkonly = false;
		}

		if (!$checkonly) {
			$this->tpl->setVariable("QUESTION_DATA", $form->getHTML());
		}
		return $errors;
	}

	/**
	 * Evaluates a posted edit form and writes the form data in the question object
	 *
	 * defined in assQuestionGUI
	 * 
	 * @param bool $always
	 * @return integer A positive value, if one of the required fields wasn't set, else 0
	 */
	public function writePostData($always = false)
	{
		$hasErrors = (!$always) ? $this->editQuestion(true) : false;
		if (!$hasErrors) {
			$this->writeQuestionGenericPostData();
			$this->writeQuestionSpecificPostData(new ilPropertyFormGUI());
			$this->writeAnswerSpecificPostData(new ilPropertyFormGUI());

			$this->saveTaxonomyAssignments();
			return 0;
		}
		return 1;
	}

	/**
	 * Get the HTML output of the question for a test
	 * SCT_VIEW_TYPE::TEST
	 * 
	 * declared in assQuestionGUI
	 * 
	 * @param integer $active_id			The active user id
	 * @param integer $pass					The test pass
	 * @param boolean $is_postponed			Question is postponed
	 * @param boolean $use_post_solutions	Use post solutions
	 * @param boolean $show_feedback		Show a feedback
	 * @return string
	 */
	public function getTestOutput($active_id, $pass = NULL, $is_postponed = FALSE, $use_post_solutions = FALSE, $show_feedback = FALSE, $show_correct_solution = false)
	{
		include_once "./Modules/Test/classes/class.ilObjTest.php";
		if (is_NULL($pass)) {
			$pass = ilObjTest::_getPass($active_id);
		}
		$solution = $this->object->getLastSolution($active_id, $pass);

		$questionoutput = $this->getQuestionOutput($solution["value1"], $solution["value2"], SCT_VIEW_TYPE::TEST);
		$pageoutput = $this->outQuestionPage("", $is_postponed, $active_id, $questionoutput);
		return $pageoutput;
	}

	/**
	 * Get the output for question preview
	 * SCT_VIEW_TYPE::PREVIEW
	 * 
	 * declared in assQuestionGUI
	 * 
	 * @param boolean	show only the question instead of embedding page (true/false)
	 */
	public function getPreview($show_question_only = FALSE, $showInlineFeedback = FALSE)
	{
		if (is_object($this->getPreviewSession())) {
			$solution = $this->getPreviewSession()->getParticipantsSolution();
		}

		$questionoutput = $this->getQuestionOutput($solution['value1'], $solution['value2'], SCT_VIEW_TYPE::PREVIEW);
		if (!$show_question_only) {
			// get page object output
			$questionoutput = $this->getILIASPage($questionoutput);
		}
		return $questionoutput;
	}

	/**
	 * Get the question solution output
	 * SCT_VIEW_TYPE::SOLUTION
	 * SCT_VIEW_TYPE::MANUAL_SCORE
	 * 
	 * declared in assQuestionGUI
	 * 
	 * @param integer $active_id             The active user id
	 * @param integer $pass                  The test pass
	 * @param boolean $graphicalOutput       Show visual feedback for right/wrong answers
	 * @param boolean $result_output         Show the reached points for parts of the question
	 * @param boolean $show_question_only    Show the question without the ILIAS content around
	 * @param boolean $show_feedback         Show the question feedback
	 * @param boolean $show_correct_solution Show the correct solution instead of the user solution
	 * @param boolean $show_manual_scoring   Show specific information for the manual scoring output
	 * @return The solution output of the question as HTML code
	 */
	function getSolutionOutput(
		$active_id,
		$pass = NULL,
		$graphicalOutput = FALSE,
		$result_output = FALSE,
		$show_question_only = TRUE,
		$show_feedback = FALSE,
		$show_correct_solution = FALSE,
		$show_manual_scoring = FALSE,
		$show_question_text = TRUE
	) {
		$solution = $this->object->getLastSolution($active_id, $pass);
		$solutionoutput = $this->getQuestionOutput($solution['value1'], $solution['value2'], SCT_VIEW_TYPE::SOLUTION, $show_question_text, $show_correct_solution);

		$res = [];
		$res["active_id"] = $active_id;
		$res["pass"] = $pass;
		$res["graphicalOutput"] = $graphicalOutput;
		$res["result_output"] = $result_output;
		$res["show_question_only"] = $show_question_only;
		$res["show_feedback"] = $show_feedback;
		$res["show_correct_solution"] = $show_correct_solution;
		$res["show_manual_scoring"] = $show_manual_scoring;
		$res["show_question_text"] = $show_question_text;
		//$solutionoutput .= json_encode($res);

		if ($show_manual_scoring) {
			$template = $this->plugin->getTemplate("tpl.il_as_qpl_srvcodeqst_empty.html");
			$this->prepareTemplate($template, SCT_VIEW_TYPE::MANUAL_SCORE);
			$template->fillJavaScriptFiles();
			$template->fillOnLoadCode();
			$template->fillCssFiles();
			$solutionoutput .= $template->get();
		}

		if (!$show_question_only) {
			$solutionoutput = $this->getILIASPage($solutionoutput);
		}

		return $solutionoutput;
	}

	/**
	 * Returns the answer specific feedback for the question
	 * 
	 * This method should be overwritten by the actual question.
	 * 
	 * declared in assQuestionGUI
	 * 
	 * @todo Mark this method abstract!
	 * @param array $userSolution ($userSolution[<value1>] = <value2>)
	 * @return string HTML Code with the answer specific feedback
	 * @access public
	 */
	function getSpecificFeedbackOutput($userSolution)
	{
		// By default no answer specific feedback is defined
		$output = '';
		return $this->object->prepareTextareaOutput($output, TRUE);
	}

	/**
	 * Sets the ILIAS tabs for this question type
	 * called from ilObjTestGUI and ilObjQuestionPoolGUI
	 * 
	 * defined in assQuestionGUI
	 * 
	 */
	public function setQuestionTabs()
	{
		global $rbacsystem, $ilTabs;

		$ilTabs->clearTargets();

		$this->ctrl->setParameterByClass("ilAssQuestionPageGUI", "q_id", $_GET["q_id"]);
		include_once "./Modules/TestQuestionPool/classes/class.assQuestion.php";
		$q_type = $this->object->getQuestionType();

		if (strlen($q_type)) {
			$classname = $q_type . "GUI";
			$this->ctrl->setParameterByClass(strtolower($classname), "sel_question_types", $q_type);
			$this->ctrl->setParameterByClass(strtolower($classname), "q_id", $_GET["q_id"]);
		}

		if ($_GET["q_id"]) {
			if ($rbacsystem->checkAccess('write', $_GET["ref_id"])) {
				// edit page
				$ilTabs->addTarget(
					"edit_page",
					$this->ctrl->getLinkTargetByClass("ilAssQuestionPageGUI", "edit"),
					array("edit", "insert", "exec_pg"),
					"",
					"",
					$force_active
				);
			}

			// edit page
			$this->addTab_QuestionPreview($ilTabs);
		}

		$force_active = false;
		if ($rbacsystem->checkAccess('write', $_GET["ref_id"])) {
			$url = "";

			if ($classname) $url = $this->ctrl->getLinkTargetByClass($classname, "editQuestion");


			// edit question properties
			$ilTabs->addTarget(
				"edit_question",
				$url,
				array("editQuestion", "save", "saveEdit", "originalSyncForm"),
				$classname,
				"",
				$force_active
			);
		}

		// add tab for question feedback within common class assQuestionGUI
		$this->addTab_QuestionFeedback($ilTabs);

		// add tab for question hint within common class assQuestionGUI
		$this->addTab_QuestionHints($ilTabs);

		// add tab for question's suggested solution within common class assQuestionGUI
		$this->addTab_SuggestedSolution($ilTabs, $classname);

		// Assessment of questions sub menu entry
		if ($_GET["q_id"]) {
			$ilTabs->addTarget(
				"statistics",
				$this->ctrl->getLinkTargetByClass($classname, "assessment"),
				array("assessment"),
				$classname,
				""
			);
		}

		$this->addBackTab($ilTabs);
	}

	//! ---------------------------------------------------------------------
	//! ------------------ ilGuiQuestionScoringAdjustable -------------------
	//! ---------------------------------------------------------------------

	/**
	 * Adds the question specific forms parts to a question property form gui.
	 * 
	 * declared in ilGuiQuestionScoringAdjustable
	 * 
	 * @param ilPropertyFormGUI $form
	 *
	 * @return ilPropertyFormGUI
	 */
	public function populateQuestionSpecificFormPart(\ilPropertyFormGUI $form)
	{
		global $lng;

		// We only add an input field for the maximum points
		// NOTE: in complex question types the maximum points are summed up by partial points
		$points = new ilNumberInputGUI($lng->txt('maximum_points'), 'points');
		$points->setSize(3);
		$points->setMinValue(1);
		$points->allowDecimals(0);
		$points->setRequired(true);
		$points->setValue($this->object->getPoints());
		$form->addItem($points);

		//| Add Server
		$url = new ilTextInputGUI($this->plugin->txt('url'), 'url');
		$url->setValue($this->object->getURL());
		$form->addItem($url);

		$token = new ilTextInputGUI($this->plugin->txt('token'), 'token');
		$token->setValue($this->object->getToken());
		$token->setValidationRegexp('/^((\s*[a-z0-9]{8}\-[a-z0-9]{4}\-[a-z0-9]{4}\-[a-z0-9]{4}\-[a-z0-9]{12}\s*)|\s*)$/');
		$form->addItem($token);

		//| Add Source Code Type Selection
		$select = new ilSelectInputGUI($this->plugin->txt('source_lang'), 'source_lang');
		$select->setOptions($this->object->getLanguages()->getName());
		$select->addCustomAttribute('onchange="selectLanguage(this)"');
		$select->setValue($this->object->getLanguage()->getKey());
		$select->setInfo($this->plugin->txt('source_lang_info'));
		$form->addItem($select);

		//| Add execution checkbox
		$allowRun = new ilCheckboxInputGUI($this->plugin->txt('allow_run'), 'allow_run');
		//$allowRun->setInfo($this->plugin->txt('allow_run_info'));
		$allowRun->setChecked($this->object->getIsAllowRun());
		$form->addItem($allowRun);

		//| Add automatic scoring checkbox
		$autoScore = new ilCheckboxInputGUI($this->plugin->txt('auto_score'), 'auto_score');
		//$allowRun->setInfo($this->plugin->txt('allow_run_info'));
		$autoScore->setChecked($this->object->getIsCalculatePoints());
		$form->addItem($autoScore);


		//| Add Code Blocks
		$item = new ilCustomInputGUI($this->plugin->txt('cq_blocks'));
		$item->setInfo($this->plugin->txt('cq_blocks_info'));
		$form->addItem($item);

		$tpl = $this->plugin->getTemplate('tpl.il_as_qpl_srvcodeqst_edit_ui.html');
		$data = "";
		for ($i = 0; $i < $this->object->getNumberOfBlocks(); $i++) {
			$data .= $this->createCodeBlockInput($i);
		}
		$tpl->setVariable("QID", $this->object->getId());
		$tpl->setVariable("BLOCK_HTML", $data);
		$tpl->setVariable("RUN_CODE_HTML", $this->createRunHTMLCode($this->object->getId(), true));
		$tpl->setVariable("TEMPLATE", $this->createCodeBlockInput(-1));

		$item = new ilCustomInputGUI('');
		$item->setHTML($tpl->get());
		$form->addItem($item);

		return $form;
	}

	/**
	 * Extracts the question specific values from $_POST and applies them
	 * to the data object.
	 *
	 * declared in ilGuiQuestionScoringAdjustable
	 *
	 * @param bool $always If true, a check for form validity is omitted.
	 *
	 * @return void
	 */
	public function writeQuestionSpecificPostData(ilPropertyFormGUI $form)
	{
		// Here you can write the question type specific values
		$this->object->setPoints((int) $_POST["points"]);
		$this->object->setLanguage((string) $_POST["source_lang"]);
		$this->object->setIsAllowRun($_POST["allow_run"]??false);
		$this->object->setIsCalculatePoints($_POST["auto_score"]??false);

		$this->object->setURL((string) $_POST["url"]);
		$this->object->setToken((string) $_POST["token"]);

		$this->object->clearBlocks();
		$i = 0;
		foreach ($_POST["block"][$this->object->getId()] as $k => $c) {
			if (!is_integer($k)) continue;
			$lns = $_POST['block_lines'][$k] + 0;
			$t = $_POST['block_type'][$k] + 0;
			$s = $_POST['block_send'][$k];
			$block = new \SCT\CodeBlock($t, isset($s), $lns, $c);
			$this->object->setBlock($i, $block);
			$i = $i + 1;
		}
	}

	/**
	 * Returns a list of postvars which will be suppressed in the form output when used in scoring adjustment.
	 * The form elements will be shown disabled, so the users see the usual form but can only edit the settings, which
	 * make sense in the given context.
	 * 
	 * declared in ilGuiQuestionScoringAdjustable
	 *
	 * E.g. array('cloze_type', 'image_filename')
	 *
	 * @return string[]
	 */
	public function getAfterParticipationSuppressionQuestionPostVars()
	{
		return $this->getAfterParticipationSuppressionAnswerPostVars();
	}

	/**
	 * Returns an html string containing a question specific representation of the answers so far
	 * given in the test for use in the right column in the scoring adjustment user interface.
	 * 
	 * declared in ilGuiQuestionScoringAdjustable
	 * 
	 * @param array $relevant_answers
	 *
	 * @return string
	 */
	public function getAggregatedAnswersView($relevant_answers)
	{
		return '';
	}
	//! ---------------------------------------------------------------------
	//! ------------------- ilGuiAnswerScoringAdjustable --------------------
	//! ---------------------------------------------------------------------
	/**
	 * Adds the answer specific form parts to a question property form gui.
	 * 
	 * declared in ilGuiAnswerScoringAdjustable
	 * 
	 * @param ilPropertyFormGUI $form
	 *
	 * @return ilPropertyFormGUI
	 */
	public function populateAnswerSpecificFormPart(\ilPropertyFormGUI $form)
	{
	}

	/**
	 * Extracts the answer specific values from $_POST and applies them to the data object.
	 * 
	 * declared in ilGuiAnswerScoringAdjustable
	 * 
	 * @param bool $always If true, a check for form validity is omitted.
	 *
	 * @return void
	 */
	public function writeAnswerSpecificPostData(ilPropertyFormGUI $form)
	{
	}

	/**
	 * Returns a list of postvars which will be suppressed in the form output when used in scoring adjustment.
	 * The form elements will be shown disabled, so the users see the usual form but can only edit the settings, which
	 * make sense in the given context.
	 *
	 * E.g. array('cloze_type', 'image_filename')
	 *
	 * declared in ilGuiAnswerScoringAdjustable
	 * @return string[]
	 */
	public function getAfterParticipationSuppressionAnswerPostVars()
	{
		return array('block', 'source_lang');
	}
}
