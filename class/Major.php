<?php

  /**
   * Major
   *
   * Models an undergraduate major. New majors will be created in future.
   * Other majors may be deleted also, so here's a class for it.
   *
   * @author Robert Bost <bostrt at tux dot appstate dot edu>
   */
PHPWS_Core::initModClass('intern', 'Model.php');
class Major extends Model
{
    public $name;
    public $hidden;

    /**
     * @Override Model::getDb
     */
    public function getDb()
    {
        return new PHPWS_DB('intern_major');
    }

    /**
     * @Override Model::getCSV
     */
    public function getCSV()
    {
        return array('Undergraduate Major' => $this->name);
    }

    public function getName()
    {
        return $this->name;
    }
    public function isHidden()
    {
        return $this->hidden == 1;
    }
    
    /**
     * Row tags for DBPager
     */
    public function getRowTags()
    {
        $tags = array();
        $tags['NAME'] = $this->name;
        // TODO: Make all these JQuery. Make edit/hide functional.
        if(Current_User::allow('intern', 'edit_major')){
            $tags['EDIT'] = 'Edit | ';
            $tags['HIDE'] = 'Hide';
        }
        if(Current_User::allow('intern', 'delete_major')){
            $div = null;
            if(isset($tags['HIDE']))
                $div = ' | ';
            $tags['DELETE'] = $div.PHPWS_Text::moduleLink('Delete','intern',array('action'=>'edit_majors','del'=>TRUE,'id'=>$this->getID()));
        }
        return $tags;
    }

    /**
     * Return an associative array {id => Major name } for all majors in DB.
     */
    public static function getMajorsAssoc()
    {
        $db = self::getDb();
        $db->addOrder('name');
        $db->addColumn('id');
        $db->addColumn('name');
        $db->addWhere('hidden', 0);
        $majors = $db->select('assoc');
        // Horrible, horrible hacks. Need to add a null selection.
        $majors = array_reverse($majors, true); // preserve keys.
        $majors[-1] = 'None';
        return array_reverse($majors, true);
    }
    
    /**
     * Add a major to DB if it does not already exist.
     */
    public static function add($name)
    {
        /* Search DB for major with matching name. */
        $db = self::getDb();
        $db->addWhere('name', $name);
        if($db->select('count') > 0){
            NQ::simple('intern', INTERN_WARNING, "The major <i>$name</i> already exists.");
            return;
        }

        /* Major does not exist...keep going */
        $major = new Major();
        $major->name = $name;
        try{
            $major->save();
        }catch(Exception $e){
            NQ::simple('intern', INTERN_ERROR, "Error adding major <i>$name</i>.<br/>".$e->getMessage());
            return;
        }

        /* Major was successfully added. */
        NQ::simple('intern', INTERN_SUCCESS, "<i>$name</i> added as undergraduate major.");
    }

    /**
     * Hide a major.
     */
    public static function hide($id)
    {
        $m = new Major($id);
        
        if($m->id == 0 || !is_numeric($m->id)){
            // Major wasn't loaded correctly
            NQ::simple('intern', INTERN_ERROR, "Error occurred while loading information for major from database.");
            return;
        }

        // Set the major's hidden flag in DB.
        $m->hidden = 1;

        try{
            $m->save();
            NQ::simple('intern', INTERN_SUCCESS, "Major <i>$m->name</i> is now hidden.");
        }catch(Exception $e){
            return NQ::simple('intern', INTERN_ERROR, $e->getMessage());
        }
    }

    /**
     * Delete a major from database by ID.
     */
    public static function del($id)
    {
        $m = new Major($id);

        if($m->id == 0){
            // Major wasn't loaded correctly
            NQ::simple('intern', INTERN_ERROR, "Error occurred while loading information for major from database.");
            return;
        }

        $name = $m->getName();
        
        try{
            // Try to delete major.
            if(!$m->delete()){
                // Something bad happend. This should have been caught in the check above...
                NQ::simple('intern', INTERN_SUCCESS, "Error occurred removing major from database.");
                return;
            }
        }catch(Exception $e){
            NQ::simple('intern', INTERN_ERROR, $e->getMessage());
            return;
        }

        // Major deleted successfully.
        NQ::simple('intern', INTERN_SUCCESS, "Deleted major <i>$name</i>");
    }
}

?>