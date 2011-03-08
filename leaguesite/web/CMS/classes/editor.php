<?php
	
	class editor
	{
		var $caller;
		private $elementButtonRequested=array();
		
		function __construct($caller)
		{
			$this->caller = $caller;
			
			global $config;
			global $tmpl;
			
			return;
		}
		
		function addFormatButtons($element)
		{
			$this->elementButtonRequested[] = $element;
		}
		
		function showFormatButtons()
		{
			global $tmpl;
			global $config;
			
			if (!$config->value('bbcodeLibAvailable'))
			{
				// no bbcode -> no buttons
				return;
			}
			
			if (count($this->elementButtonRequested) < 0)
			{
				// no buttons added -> nothing to show
				return;
			}
			
			// TODO: does also link to included javascript each time called
			// TODO: Make it do this only once
			include(dirname(dirname(__FILE__)) . '/bbcode_buttons.php');
			$bbcode = new bbcode_buttons();
			
			foreach ($this->elementButtonRequested as $element)
			{
				$buttons = $bbcode->showBBCodeButtons($element);
				$tmpl->assign('buttonsToFormat', $buttons);
			}
		}
		
		
		function edit()
		{
			global $entry_edit_permission;
			global $randomKeyName;
			global $config;
			global $site;
			global $tmpl;
			global $user;
			
			
			if ($user->getPermission($entry_edit_permission))
			{
				// initialise variables
				$confirmed = 0;
				$content = '';
				
				// set their values in case the POST variables are set
				if (isset($_POST['confirmationStep']))
				{
					$confirmed = intval($_POST['confirmationStep']);
				}
				if (isset($_POST['editPageAgain']) && strlen($_POST['editPageAgain']) > 0)
				{
					// user looked at preview but chose to edit the message again
					$confirmed = 0;
				}
				if (isset($_POST['staticContent']))
				{
					$content = htmlspecialchars_decode($_POST['staticContent'], ENT_COMPAT);
				}
				
				// sanity check variabless
				$test = $this->caller->sanityCheck($confirmed);
				switch ($test)
				{
						// use bbcode if available
					case (true && $confirmed === 1 && $config->value('bbcodeLibAvailable')):
						$this->caller->insertEditText(true);
/* 						$tmpl->addMSG($tmpl->encodeBBCode($content)); */
						break;
						
						// else raw output
					case (true && $confirmed === 1 && !$config->value('bbcodeLibAvailable')):
						$this->caller->insertEditText(true);
/* 						$tmpl->addMSG($content); */
						break;
						
						// use this as guard to prevent selection of noperm or nokeymatch cases
					case (strlen($test) < 2):
						$this->caller->insertEditText(false);
						break;
						
					case 'noperm':
						$tmpl->assign('MSG', 'You need write permission to edit the content.');
						break;
						
					case 'nokeymatch':
						$this->caller->insertEditText(false);
						$tmpl->assign('MSG', 'The magic key does not match, it looks like you came from somewhere else or your session expired.');
						break;			
				}
				unset($test);
				
				
				// there is no step lower than 0
				if ($confirmed < 0)
				{
					$confirmed = 0;
				}
				
				// increase confirmation step by one so we get to the next level
/* 				$tmpl->setCurrentBlock('PREVIEW_VALUE'); */
				if ($confirmed > 1)
				{
					$tmpl->assign('confirmationStep', 1);
				} else
				{
					$tmpl->assign('confirmationStep', $confirmed+1);
				}
/* 				$tmpl->parseCurrentBlock(); */
				
				switch ($confirmed)
				{
					case 1:
						$tmpl->assign('submitText', 'Write changes');
						// user may decide not to submit after seeing preview
						$tmpl->assign('editAgainText', 'Edit again');
						break;
						
					case 2:
						$this->caller->writeContent($content, $page_title);
						$tmpl->addMSG('Changes written successfully.' . $tmpl->linebreaks("\n\n"));
						
					default:
						$tmpl->assign('USER_NOTE');
						
						if ($config->value('bbcodeLibAvailable'))
						{
							$tmpl->assign('notes', 'Keep in mind to use BBCode instead of HTML or XHTML.');
/* 							$tmpl->parseCurrentBlock(); */
						} else
						{
							if ($config->value('useXhtml'))
							{
								$tmpl->assign('notes', 'Keep in mind the home page currently uses XHTML, not HTML or BBCode.');
							} else
							{
								$tmpl->assign('notes', 'Keep in mind the home page currently uses HTML, not XHTML or BBCode.');
							}
						}
						$tmpl->assign('submitText', 'Preview');
				}
				
				
				$randomKeyName = $randomKeyName . microtime();
				// convert some special chars to underscores
				$randomKeyName = strtr($randomKeyName, array(' ' => '_', '.' => '_'));
				$randomkeyValue = $site->setKey($randomKeyName);
/* 				$tmpl->setCurrentBlock('KEY'); */
				$tmpl->assign('keyName', $randomKeyName);
				$tmpl->assign('keyValue', urlencode($_SESSION[$randomKeyName]));
/* 				$tmpl->parseCurrentBlock(); */
			}
		}
	}
	
?>
