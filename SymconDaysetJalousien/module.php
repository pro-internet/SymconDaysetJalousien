<?
class Dayset_Jalousie extends IPSModule {

	private $InstanceParentID;
	private $dummyGUID;
	
	protected function GetModuleIDByName($name)
	{
		$moduleList = IPS_GetModuleList();
		$GUID = ""; //init
		foreach($moduleList as $l)
		{
			if(IPS_GetModule($l)['ModuleName'] == $name)
			{
				$GUID = $l;
				break;
			}
		}
		return $GUID;
	}
	
	protected function CreateSetValueScript($parentID)
	{
		if(@IPS_GetObjectIDByIdent("SetValueScript", $parentID) === false)
		{
			$sid = IPS_CreateScript(0 /* PHP Script */);
		}
		else
		{
			$sid = IPS_GetObjectIDByIdent("SetValueScript", $parentID);
		}
		IPS_SetParent($sid, $parentID);
			IPS_SetName($sid, "SetValue");
			IPS_SetIdent($sid, "SetValueScript");
			IPS_SetHidden($sid, true);
			IPS_SetPosition($sid, 9999);			
			IPS_SetScriptContent($sid, "<?

if (\$IPS_SENDER == \"WebFront\") 
{ 
    SetValue(\$_IPS['VARIABLE'], \$_IPS['VALUE']); 
} 

?>");

		return $sid;
	}
	
	protected function CreateLink($target, $ident, $parentID, $position)
	{
		if(@IPS_GetObjectIDByIdent($ident, $parentID) === false)
		{
			$lid = IPS_CreateLink();
		}
		else
		{
			$lid = IPS_GetObjectIDByIdent($ident, $parentID);
		}
		$o = IPS_GetObject($target);
		IPS_SetIdent($lid, $ident);
		IPS_SetName($lid, $o['ObjectName']);
		IPS_SetParent($lid, $parentID);
		IPS_SetPosition($lid, $position);
		IPS_SetLinkTargetID($lid, $target);
	}
	
	protected function CreateEvent($name, $ident, $parentID, $type, $trigger, $target, $script)
	{
		if(@IPS_GetObjectIDByIdent($ident, $parentID) === false)
		{
			$eid = IPS_CreateEvent($type);
		}
		else
		{
			$eid = IPS_GetObjectIDByIdent($ident, $parentID);
		}
		IPS_SetEventActive($eid, true);
		IPS_SetEventTrigger($eid, $trigger, $target);
		IPS_SetEventScript($eid, $script);
		IPS_SetName($eid, $name);
		IPS_SetIdent($eid, $ident);
		IPS_SetParent($eid, $parentID);
		
		return $eid;
	}
	
	protected function CreateEventForChildren($parentID, $eventsParent)
	{
		$targets = IPS_GetChildrenIDs($parentID);
		$parentName = IPS_GetName($parentID);
		foreach($targets as $target)
		{
			if(IPS_VariableExists($target))
			{
				$o = IPS_GetObject($target);
				$name = $parentName . $o['ObjectName'] . "onchange";
				$ident = $parentName . $o['ObjectIdent'] . "onchange";
				$this->CreateEvent($name, $ident, $eventsParent, 0, 1, $target, "DSJal_refresh(" . $this->InstanceID . "," . $target . ");");
			}
		}
	}
	
	protected function CreateSelectProfile()
	{
		if(!IPS_VariableProfileExists("DSJal.Selector"))
			IPS_CreateVariableProfile("DSJal.Selector", 1 /* Int */);
		IPS_SetVariableProfileIcon("DSJal.Selector", "Shutter");
		IPS_SetVariableProfileValues("DSJal.Selector", 0, 4, 0);
		IPS_SetVariableProfileAssociation("DSJal.Selector", 0, "Offen", "", -1);
		IPS_SetVariableProfileAssociation("DSJal.Selector", 1, "Geschlossen", "", -1);
		IPS_SetVariableProfileAssociation("DSJal.Selector", 2, "Ausblick", "", -1);
		IPS_SetVariableProfileAssociation("DSJal.Selector", 3, "Beschattung", "", -1);
		IPS_SetVariableProfileAssociation("DSJal.Selector", 4, "Sonnenschutz", "", -1);
	}
	
	protected function CreateInstance($GUID, $name, $ident, $parentID = 0, $position = 0)
	{
		if(@IPS_GetObjectIDByIdent($ident, $parentID) === false)
		{
			$insID = IPS_CreateInstance($GUID);
		}
		else
		{
			$insID = IPS_GetObjectIDByIdent($ident, $parentID);
		}
		IPS_SetName($insID, $name);
		IPS_SetIdent($insID, $ident);
		if($parentID == 0)
			$parentID = $this->InstanceID;
		IPS_SetParent($insID, $parentID);
		IPS_SetPosition($insID, $position);
		
		return $insID;
	}
	
	protected function CreateVariable($type, $name, $ident, $parentID = 0, $position = 0, $initVal = 0, $profile = "", $actionID = "SetValue")
	{
		if(@IPS_GetObjectIDByIdent($ident, $parentID) === false)
		{
			$varID = IPS_CreateVariable($type);
			SetValue($varID, $initVal);
		}
		else
		{
			$varID = IPS_GetObjectIDByIdent($ident, $parentID);
		}
		IPS_SetName($varID, $name);
		IPS_SetIdent($varID, $ident);
		if($parentID == 0)
			$parentID = $this->InstanceID;
		IPS_SetParent($varID, $parentID);
		IPS_SetPosition($varID, $position);
		if(IPS_VariableProfileExists($profile))
			IPS_SetVariableCustomProfile($varID,$profile);
		if($actionID == "SetValue")
			$actionID = $this->CreateSetValueScript($this->InstanceParentID);
		if($actionID > 9999)
			IPS_SetVariableCustomAction($varID,$actionID);
		
		return $varID;
	}
	
	protected function CreateCategory($name, $ident, $parentID = 0 , $position = 0)
	{
		if(@IPS_GetObjectIDByIdent($ident, $parentID) === false)
		{
			$catID = IPS_CreateCategory();
		}
		else
		{
			$catID = IPS_GetObjectIDByIdent($ident, $parentID);
		}
		IPS_SetName($catID, $name);
		IPS_SetIdent($catID, $ident);
		if($parentID == 0)
			$parentID = $this->InstanceID;
		IPS_SetParent($catID, $parentID);
		IPS_SetPosition($catID, $position);
		
		return $catID;
	}

	//////////////////////////////
	// Module Controlls /*MDC*/ //
	//////////////////////////////
	
	public function __construct($InstanceID) {
            //Never delete this line!
            parent::__construct($InstanceID);
			$this->dummyGUID = $this->GetModuleIDByName("Dummy Module");
        }
	
	public function Create() {
		//Never delete this line!
		parent::Create();
		
		//Register Properties
		if(@$this->RegisterPropertyString("Raeume") !== false)
		{
			$this->RegisterPropertyString("Raeume","");
			$this->RegisterPropertyInteger("DaysetVar",0);
		}	
		//Define "Räume" Module as this->InstanceID
		IPS_SetIdent($this->InstanceID, "RaeumeIns");
		IPS_SetPosition($this->InstanceID, 3);
		
		//Create Selector Profile
		$this->CreateSelectProfile();
	}

	public function Destroy() {
		//Never delete this line!
		parent::Destroy();
		
	}

	public function ApplyChanges() {
		//Never delete this line!
		parent::ApplyChanges();
		//Define Instance Parent
		$this->InstanceParentID = IPS_GetParent($this->InstanceID);
		
		if($this->InstanceParentID != 0)
		{
			IPS_SetIcon($this->InstanceID, "Jalousie");
			
			//Create the Dummy Modules
			$dummyGUID = $this->GetModuleIDByName("Dummy Module");
			$this->CreateInstance($this->dummyGUID, "Werte", "WerteIns", $this->InstanceParentID, 0);
			$this->CreateInstance($this->dummyGUID, "Automatik", "AutomatikIns", $this->InstanceParentID, 1);
			$this->CreateInstance($this->dummyGUID, "Tageszeiten", "TageszeitenIns", $this->InstanceParentID, 2);
			
			//Get Content of Table
			$dataJSON = $this->ReadPropertyString("Raeume");
			$data = json_decode($dataJSON);
			
			//Create Events Folder
			$EventCatID = $this->CreateCategory("Events", "EventsCat", $this->InstanceParentID, 9998);
			IPS_SetHidden($EventCatID, true);
				
			//Create Objects for "Werte"
			$insID = IPS_GetObjectIDByIdent("WerteIns", $this->InstanceParentID);
			$this->CreateVariable(0, "Offen", "OffenVar", $insID, 0, true, "~Switch", "SetValue");
			$this->CreateVariable(0, "Geschlossen", "GeschlossenVar", $insID, 1, false, "~Switch", "SetValue");
			$this->CreateInstance($this->dummyGUID, "Ausblick", "AusblickIns", $insID, 2);
			$this->CreateVariable(1, "Behang", "AusblickBehangVar", $insID, 3, 0, "~Shutter", "SetValue");
			$this->CreateVariable(1, "Lamellen", "AusblickLamellenVar", $insID, 4, 0, "~Shutter", "SetValue");
			$this->CreateInstance($this->dummyGUID, "Beschattung", "BeschattungIns", $insID, 5);
			$this->CreateVariable(1, "Behang", "BeschattungBehangVar", $insID, 6, 0, "~Shutter", "SetValue");
			$this->CreateVariable(1, "Lamellen", "BeschattungLamellenVar", $insID, 7, 0, "~Shutter", "SetValue");
			$this->CreateInstance($this->dummyGUID, "Sonnenschutz", "SonnenschutzIns", $insID, 8);
			$this->CreateVariable(1, "Behang", "SonnenschutzBehangVar", $insID, 9, 0, "~Shutter", "SetValue");
			$this->CreateVariable(1, "Lamellen", "SonnenschutzLamellenVar", $insID, 10, 0, "~Shutter", "SetValue");
			
			//Create onchange Events for all Variables in "Werte"
			$this->CreateEventForChildren($insID, $EventCatID);
			
			//Create Objects for "Automatik"
			$insID = IPS_GetObjectIDByIdent("AutomatikIns", $this->InstanceParentID);
			foreach($data as $id => $content)
			{
				$vid = $this->CreateVariable(0, $content->Raumname, "raum$id", $insID, $id, false, "~Switch", "SetValue");
				$eid = $this->CreateEvent("Automatikraum$id" . "onchange", "Automatikraum$id" . "onchange", $EventCatID, 0, 4, $vid, "DSJal_refresh(". $this->InstanceID . "," . $vid . ");");
				IPS_SetEventTriggerValue($eid, true);
			}
			
			//Create Objects for "Tageszeiten"
			$insID = IPS_GetObjectIDByIdent("TageszeitenIns", $this->InstanceParentID);
			//Create Link to Dayset
			$target = $this->ReadPropertyInteger("DaysetVar");
			$this->CreateLink($target, "DaysetLink", $insID, -9999);
			
			//Create Dayset Event
			$this->CreateEvent("Daysetonchange", "DaysetEvent", $EventCatID, 0, 1, $target, "DSJal_refresh(" . $this->InstanceID . "," . $target . ");");
			
				//Früh
				$id = $this->CreateInstance($this->dummyGUID, "Früh", "FruehIns", $insID, -1);
				//IPS_SetIcon($id, "Fog");
				foreach($data as $id => $content)
				{
					$this->CreateVariable(1, $content->Raumname, "Fruehraum$id", $insID, $id, 0, "DSJal.Selector", "SetValue");
				}
				//Sonnenaufgang
				$this->CreateInstance($this->dummyGUID, "Sonnenaufgang", "SonnenaufgangIns", $insID, count($data));
				foreach($data as $id => $content)
				{
					$this->CreateVariable(1, $content->Raumname, "Sonnenaufgangraum$id", $insID, $id + 1 + count($data), 0, "DSJal.Selector", "SetValue");
				}
				//Tag
				$id = $this->CreateInstance($this->dummyGUID, "Tag", "TagIns", $insID, count($data) * 2 + 1);
				//IPS_SetIcon($id, "Sun");
				foreach($data as $id => $content)
				{
					$this->CreateVariable(1, $content->Raumname, "Tagraum$id", $insID, $id + 1 + count($data) * 2 + 1, 0, "DSJal.Selector", "SetValue");
				}
				//Dämmerung
				$this->CreateInstance($this->dummyGUID, "Dämmerung", "DaemmerungIns", $insID, count($data) * 3 + 2);
				foreach($data as $id => $content)
				{
					$this->CreateVariable(1, $content->Raumname, "Daemmerungraum$id", $insID, $id + 1 + count($data) * 3 + 2, 0, "DSJal.Selector", "SetValue");
				}
				//Abend
				$id = $this->CreateInstance($this->dummyGUID, "Abend", "AbendIns", $insID, count($data) * 4 + 3);
				//IPS_SetIcon($id, "Moon");
				foreach($data as $id => $content)
				{
					$this->CreateVariable(1, $content->Raumname, "Abendraum$id", $insID, $id + 1 + count($data) * 4 + 3, 0, "DSJal.Selector", "SetValue");
				}
				//Nacht
				$id = $this->CreateInstance($this->dummyGUID, "Nacht", "NachtIns", $insID, count($data) * 5 + 4);
				//IPS_SetIcon($id, "Moon");
				foreach($data as $id => $content)
				{
					$this->CreateVariable(1, $content->Raumname, "Nachtraum$id", $insID, $id + 1 + count($data) * 5 + 4, 0, "DSJal.Selector", "SetValue");
				}
				$this->CreateEventForChildren($insID, $EventCatID);
			
			//Create Objects for "Räume"
			$insID = $this->InstanceID;
			foreach($data as $id => $content)
			{
				$catID = $this->CreateCategory($content->Raumname . ".Targets", "Targetsraum$id", $insID, $id - count($data));
				$this->CreateCategory("Jalousie", "Jalousie", $catID, 0);
				$this->CreateCategory("Lamellen", "Lamellen", $catID, 1);
				$this->CreateCategory("Switch", "Switch", $catID, 2);
				$vid = $this->CreateVariable(1, $content->Raumname, "raum$id", $insID, $id, 0, "DSJal.Selector", "SetValue");
				$this->CreateEvent($content->Raumname . "OnChange", "raum$id" . "onchange", $EventCatID, 0, 0, $vid, "DSJal_SetValue(" . $this->InstanceID . "," . "\"raum$id\");");
			}
			$insID = IPS_GetObjectIDByIdent("AutomatikIns", $this->InstanceParentID);
			if(count($data) < count(IPS_GetChildrenIDs($insID)))
			{
				$children = IPS_GetChildrenIDs($insID);
				$insID = IPS_GetObjectIDByIdent("TageszeitenIns", $this->InstanceParentID);
				for($i = count($data); $i < count($children); $i++)
				{
					$ident = IPS_GetObject($children[$i])['ObjectIdent'];
					IPS_DeleteVariable($children[$i]);
					$child = IPS_GetObjectIDByIdent($ident, $this->InstanceID);
					IPS_DeleteVariable($child);
					$cids = IPS_GetChildrenIDs($insID);
					foreach($cids as $childID)
					{
						$TageszeitenIdent = IPS_GetObject($childID)['ObjectIdent'];
						if(strpos($TageszeitenIdent, $ident) !== false)
							IPS_DeleteVariable($childID);
					}
				}
			}
		}
	}
	
	private function SetValueByDevice($insID, $value)
	{
		if(gettype($value) == "boolean")
		{
			EIB_Switch($insID, $value);
		}
		else
		{
			EIB_DriveShutterValue($insID, $value);
		}
	}
	
	private function Set($targetFolder, $value)
	{
		$targets = IPS_GetChildrenIDs($targetFolder);
		foreach($targets as $target) 
		{
			if(IPS_LinkExists($target)) //only allow links
			{
				$target = IPS_GetLink($target)['TargetID'];
				if(IPS_InstanceExists($target))
				{
					$insID = $target;
					$target = @IPS_GetChildrenIDs($target)[0];
				}
				if (IPS_VariableExists($target))
				{
					$o = IPS_GetObject($target);
					$v = IPS_GetVariable($target);
					$currentValue = GetValue($target);
					if(gettype($value) == "boolean" || $currentValue != $value)
					{	
						$switchValue = true;
					}
					else
					{
						$switchValue = false;
					}
					
					if($switchValue)
					{
						if($v['VariableCustomAction'] > 0)
							$actionID = $v['VariableCustomAction'];
						else
							$actionID = $v['VariableAction'];
						
						//try changing the value by device-specific commands
						if($actionID < 10000)
						{
							if(@$insID != NULL)
								$this->SetValueByDevice($insID, $value);
							SetValue($target, $value);
							//Skip this device if we do not have a proper id
							continue;
						}
							
						if(IPS_InstanceExists($actionID)) {
							IPS_RequestAction($actionID, $o['ObjectIdent'], $value);
						} else if(IPS_ScriptExists($actionID)) {
							echo IPS_RunScriptWaitEx($actionID, Array("VARIABLE" => $id, "VALUE" => $value, "SENDER" => "WebFront"));
						}	
					}
				}
				else
				{
					SetValueByDevice($insID, $value);
				}
			}
			else
			{
				throw new Exception('Only Links as Targets allowed');
			}
		}
	}
	
	////////////////////
	//public functions//
	////////////////////
	
	public function SetValue($ident)
	{
		$this->InstanceParentID = IPS_GetParent($this->InstanceID);
		$roomVar = IPS_GetObjectIDByIdent($ident, $this->InstanceID);
		$WerteIns = IPS_GetObjectIDByIdent("WerteIns", $this->InstanceParentID);
		
		//Get the Targets
		$targetsCatID = IPS_GetObjectIDByIdent("Targets$ident", $this->InstanceID);
		$targetsJal = IPS_GetObjectIDByIdent("Jalousie", $targetsCatID);
		$targetsLam = IPS_GetObjectIDByIdent("Lamellen", $targetsCatID);
		$targetsSwi = IPS_GetObjectIDByIdent("Switch", $targetsCatID);
		
		switch(GetValue($roomVar))
		{
			case(0 /*Offen*/):
				//Get the Values
				$vid = IPS_GetObjectIDByIdent("OffenVar", $WerteIns);
				$value = GetValue($vid);
				$this->Set($targetsSwi, $value);
				break;
			case(1 /*Geschlossen*/):
				//Get the Values
				$vid = IPS_GetObjectIDByIdent("GeschlossenVar", $WerteIns);
				$value = GetValue($vid);
				$this->Set($targetsSwi, $value);
				break;
			case(2 /*Ausblick*/):
				//Get the Values
				//Behang
				$vid = IPS_GetObjectIDByIdent("AusblickBehangVar", $WerteIns);
				$value = GetValue($vid);
				$this->Set($targetsJal, $value);
				//Lamellen
				$vid = IPS_GetObjectIDByIdent("AusblickLamellenVar", $WerteIns);
				$value = GetValue($vid);
				$this->Set($targetsLam, $value);
				break;
			case(3 /*Beschattung*/):
				//Get the Values
				//Behang
				$vid = IPS_GetObjectIDByIdent("BeschattungBehangVar", $WerteIns);
				$value = GetValue($vid);
				$this->Set($targetsJal, $value);
				//Lamellen
				$vid = IPS_GetObjectIDByIdent("BeschattungLamellenVar", $WerteIns);
				$value = GetValue($vid);
				$this->Set($targetsLam, $value);
				break;
			case(4 /*Sonnenschutz*/):
				//Get the Values
				//Behang
				$vid = IPS_GetObjectIDByIdent("SonnenschutzBehangVar", $WerteIns);
				$value = GetValue($vid);
				$this->Set($targetsJal, $value);
				//Lamellen
				$vid = IPS_GetObjectIDByIdent("SonnenschutzLamellenVar", $WerteIns);
				$value = GetValue($vid);
				$this->Set($targetsLam, $value);
				break;
			default:
				echo "index not found: " . GetValue($roomVar);
		}
	}
	
	public function refresh($sender)
	{
		//Get Content of Table
		$dataJSON = $this->ReadPropertyString("Raeume");
		$data = json_decode($dataJSON);
		
		$this->InstanceParentID = IPS_GetParent($this->InstanceID);
		$automatikIns = IPS_GetObjectIDByIdent("AutomatikIns", $this->InstanceParentID);
		$tageszeitenIns = IPS_GetObjectIDByIdent("TageszeitenIns", $this->InstanceParentID);
		$daysetVar = $this->ReadPropertyInteger("DaysetVar");
		foreach($data as $id => $content)
		{
			switch(GetValue($daysetVar))
			{
				case(1 /*Früh*/):
					$vid = IPS_GetObjectIDByIdent("Fruehraum$id", $tageszeitenIns);
					break;
				case(2 /*Sonnenaufgang*/):
					$vid = IPS_GetObjectIDByIdent("Sonnenaufgangraum$id", $tageszeitenIns);
					break;
				case(3 /*Tag*/):
					$vid = IPS_GetObjectIDByIdent("Tagraum$id", $tageszeitenIns);
					break;
				case(4 /*Dämmerung*/):
					$vid = IPS_GetObjectIDByIdent("Daemmerungraum$id", $tageszeitenIns);
					break;
				case(5 /*Abend*/):
					$vid = IPS_GetObjectIDByIdent("Abendraum$id", $tageszeitenIns);
					break;
				case(6 /*Nacht*/):
					$vid = IPS_GetObjectIDByIdent("Nachtraum$id", $tageszeitenIns);
					break;
			}
			$raumID = IPS_GetObjectIDByIdent("raum$id", $this->InstanceID);
			$automatikRaumID = IPS_GetObjectIDByIdent("raum$id", $automatikIns);
			$automatik = GetValue($automatikRaumID);
			$vIdent = IPS_GetObject($sender)['ObjectIdent'];
			if($automatik && ($sender == $daysetVar /*sender = dayset*/ || strpos($vIdent, "raum") !== false /*sender = Automatik || Tageszeiten*/))
			{
				$value = GetValue($vid);
				SetValue($raumID, $value);
			}
			else
			{
				$o = IPS_GetObject($sender);
				$i = $o['ObjectIdent'];
				if(strpos($i, "Offen") !== false)
				{
					if(GetValue($raumID) == 0)
						SetValue($raumID, 0);
				} else if(strpos($i, "Geschlossen") !== false)
				{
					if(GetValue($raumID) == 1)
						SetValue($raumID, 1);
				} else if(strpos($i, "Ausblick") !== false)
				{
					if(GetValue($raumID) == 2)
						SetValue($raumID, 2);
				} else if(strpos($i, "Beschattung") !== false)
				{
					if(GetValue($raumID) == 3)
						SetValue($raumID, 3);
				} else if(strpos($i, "Sonnenschutz") !== false)
				{
					if(GetValue($raumID) == 4)
						SetValue($raumID, 4);
				}
			}
		}
	}
}
?>