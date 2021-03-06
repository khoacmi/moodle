<?php
/**
 * Created by PhpStorm.
 * User: justin
 * Date: 17/08/29
 * Time: 16:12
 */

namespace mod_minilesson;


class comprehensiontest
{
    protected $cm;
    protected $context;
    protected $mod;
    protected $items;

    public function __construct($cm) {
        global $DB;
        $this->cm = $cm;
        $this->mod = $DB->get_record(constants::M_TABLE, ['id' => $cm->instance], '*', MUST_EXIST);
        $this->context = \context_module::instance($cm->id);
        $this->course =$DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    }

    public function fetch_item_count()
    {
        global $DB;
        if (!$this->items) {
            $this->items = $DB->get_records(constants::M_QTABLE, ['minilesson' => $this->mod->id],'itemorder ASC');
        }
        if($this->items){
            return count($this->items);
        }else{
            return 0;
        }
    }

    public function fetch_media_urls($filearea,$item){
        //get question audio div (not so easy)
        $fs = get_file_storage();
        $files = $fs->get_area_files($this->context->id,  constants::M_COMPONENT,$filearea,$item->id);
        $urls=[];
        foreach ($files as $file) {
            $filename = $file->get_filename();
            if($filename=='.'){continue;}
            $filepath = '/';
            $mediaurl = \moodle_url::make_pluginfile_url($this->context->id, constants::M_COMPONENT,
                $filearea, $item->id,
                $filepath, $filename);
            $urls[]= $mediaurl->__toString();

        }
        return $urls;
       // return "$this->context->id pp $filearea pp $item->id";
    }

    public function fetch_items()
    {
        global $DB;
        if (!$this->items) {
            $this->items = $DB->get_records(constants::M_QTABLE, ['minilesson' => $this->mod->id],'itemorder ASC');
        }
        if($this->items){
            return $this->items;
        }else{
            return [];
        }
    }

    public function fetch_latest_attempt($userid){
        global $DB;

        $attempts = $DB->get_records(constants::M_ATTEMPTSTABLE,array('moduleid' => $this->mod->id,'userid'=>$userid),'id DESC');
        if($attempts){
            $attempt = array_shift($attempts);
            return $attempt;
        }else{
            return false;
        }
    }

    /*we will probably never need to use this again */
    public function fetch_test_data_for_js_files(){

        $items = $this->fetch_items();

        //prepare data array for test
        $testitems=array();
        $currentitem=0;
        $itemcount=count($items);
        $itemid= $this->cm->instance;
        foreach($items as $item) {
            $currentitem++;
            $testitem= new \stdClass();
            $testitem->number =  $currentitem;
            //text area
            $testitem->text =  file_rewrite_pluginfile_urls($item->{constants::TEXTQUESTION},
                'pluginfile.php', $this->context->id,constants::M_COMPONENT,
                constants::TEXTQUESTION_FILEAREA, $itemid);

            //Question media embed
            if(!empty(trim($item->{constants::MEDIAIFRAME}))){
                $testitem->itemiframe=$item->{constants::MEDIAIFRAME};
            }

            //media items
            $mediaurls =$this->fetch_media_urls(constants::MEDIAQUESTION,$item);
            if($mediaurls && count($mediaurls)>0){
                foreach($mediaurls as $mediaurl){
                    $file_parts = pathinfo(strtolower($mediaurl));
                    switch($file_parts['extension'])
                    {
                        case "jpg":
                        case "png":
                        case "gif":
                        case "bmp":
                        case "svg":
                            $testitem->itemimage = $mediaurl;
                            break;

                        case "mp4":
                        case "mov":
                        case "webm":
                        case "ogv":
                            $testitem->itemvideo = $mediaurl;
                            break;

                        case "mp3":
                        case "ogg":
                        case "wav":
                            $testitem->itemaudio = $mediaurl;
                            break;

                        default:
                            //do nothing
                    }//end of extension switch
                }//end of for each
            }//end of if mediaurls

            for($anumber=1;$anumber<=constants::MAXANSWERS;$anumber++) {
                $testitem->{'customtext' . $anumber} = file_rewrite_pluginfile_urls($item->{constants::TEXTANSWER . $anumber},
                    'pluginfile.php', $this->context->id,constants::M_COMPONENT,
                    constants::TEXTANSWER_FILEAREA . $anumber, $itemid);
            }
            $testitem->correctanswer =  $item->correctanswer;
            $testitem->id = $item->id;
            $testitem->type=$item->type;
            $testitems[]=$testitem;
        }
        return $testitems;
    }

    /* return the test items suitable for js to use */
    public function fetch_test_data_for_js($forcetitles=false){
        global $CFG, $USER;

        $items = $this->fetch_items();

        //first confirm we are authorised before we try to get the token
        $config = get_config(constants::M_COMPONENT);
        if(empty($config->apiuser) || empty($config->apisecret)){
            $errormessage = get_string('nocredentials',constants::M_COMPONENT,
                    $CFG->wwwroot . constants::M_PLUGINSETTINGS);
            //return error?
            $token=false;
        }else {
            //fetch token
            $token = utils::fetch_token($config->apiuser,$config->apisecret);

            //check token authenticated and no errors in it
            $errormessage = utils::fetch_token_error($token);
            if(!empty($errormessage)){
                //return error?
                //return $this->show_problembox($errormessage);
            }
        }

        //editor options
        $editoroptions = \mod_minilesson\local\rsquestion\helper::fetch_editor_options($this->course, $this->context);

        //prepare data array for test
        $testitems=array();
        $currentitem=0;
        foreach($items as $item) {
            $currentitem++;
            $testitem= new \stdClass();
            $testitem->number =  $currentitem;
            $testitem->correctanswer =  $item->correctanswer;
            $testitem->id = $item->id;
            $testitem->type=$item->type;
            if($this->mod->showqtitles||$forcetitles){$testitem->title=$item->name;}
            $testitem->uniqueid=$item->type . $testitem->number;
            switch($testitem->type) {
                case constants::TYPE_DICTATION:
                case constants::TYPE_DICTATIONCHAT:
                case constants::TYPE_SPEECHCARDS:
                case constants::TYPE_LISTENREPEAT:
                case constants::TYPE_MULTICHOICE:
                case constants::TYPE_PAGE:
                case constants::TYPE_TEACHERTOOLS:
                case constants::TYPE_SHORTANSWER:

                    //Question Text
                    $testitem->text =  file_rewrite_pluginfile_urls($item->{constants::TEXTQUESTION},
                            'pluginfile.php', $this->context->id,constants::M_COMPONENT,
                            constants::TEXTQUESTION_FILEAREA, $testitem->id);
                    $testitem->text =format_text($testitem->text,FORMAT_MOODLE ,$editoroptions);

                    //Question media embed
                    if(!empty(trim($item->{constants::MEDIAIFRAME}))){
                        $testitem->itemiframe=$item->{constants::MEDIAIFRAME};
                    }

                    //Question media items (upload)
                    $mediaurls =$this->fetch_media_urls(constants::MEDIAQUESTION,$item);
                    if($mediaurls && count($mediaurls)>0){
                        foreach($mediaurls as $mediaurl){
                            $file_parts = pathinfo(strtolower($mediaurl));
                            switch($file_parts['extension'])
                            {
                                case "jpg":
                                case "png":
                                case "gif":
                                case "bmp":
                                case "svg":
                                    $testitem->itemimage = $mediaurl;
                                    break;

                                case "mp4":
                                case "mov":
                                case "webm":
                                case "ogv":
                                    $testitem->itemvideo = $mediaurl;
                                    break;

                                case "mp3":
                                case "ogg":
                                case "wav":
                                    $testitem->itemaudio = $mediaurl;
                                    break;

                                default:
                                    //do nothing
                            }//end of extension switch
                        }//end of for each
                    }//end of if mediaurls

                    break;
                default:
                    $testitem->text =  $item->{constants::TEXTQUESTION};
                    $testitem->text =format_text($testitem->text);
                    break;
            }


            for($anumber=1;$anumber<=constants::MAXANSWERS;$anumber++) {
                if(!empty(trim($item->{constants::TEXTANSWER . $anumber}))) {
                    $testitem->{'customtext' . $anumber} = $item->{constants::TEXTANSWER . $anumber};
                }
            }


            switch($testitem->type){
                case constants::TYPE_DICTATION:
                case constants::TYPE_DICTATIONCHAT:
                case constants::TYPE_SPEECHCARDS:
                case constants::TYPE_LISTENREPEAT:
                   $sentences = explode(PHP_EOL,$testitem->customtext1);
                   $index=0;
                    $testitem->sentences=[];
                   foreach($sentences as $sentence){
                       $sentence = trim($sentence);
                       if(empty($sentence)){continue;}
                       //if we have a pipe we are listening for array[0] and displaying array[1]
                       $sentencebits = explode('|',$sentence);
                       if(count($sentencebits)>1){
                           $sentence = trim($sentencebits[0]);
                           $displaysentence = trim($sentencebits[1]);
                       }else{
                           $displaysentence = $sentence;
                       }

                       $s = new \stdClass();
                       $s->index=$index;
                       $s->sentence=$sentence;
                       $s->displaysentence=$displaysentence;
                       $s->length = strlen($s->sentence);
                       $index++;
                       $testitem->sentences[]=$s;
                   }

                   //cloudpoodll stuff
                   $testitem->region =$config->awsregion;
                   $testitem->cloudpoodlltoken = $token;
                   $testitem->wwwroot=$CFG->wwwroot;
                   $testitem->language=$this->mod->ttslanguage;
                   $testitem->hints='';
                   $testitem->owner=hash('md5',$USER->username);
                   $testitem->usevoice=$item->{constants::POLLYVOICE};

                   //TT Recorder stuff
                   $testitem->waveheight = 75;
                   //passagehash for several reasons could rightly be empty
                   //if its full it will be region|hash eg tokyo|2353531453415134545
                   //we just want the hash here
                   $testitem->passagehash="";
                   if(!empty($item->passagehash)){
                        $hashbits = explode('|',$item->passagehash,2);
                        if(count($hashbits)==2){
                            $testitem->passagehash  = $hashbits[1];
                        }
                    }

                   switch($this->mod->region) {
                       case 'tokyo':
                           $testitem->asrurl = 'https://tokyo.ls.poodll.com/transcribe';
                           break;
                       case 'dublin':
                           $testitem->asrurl = 'https://dublin.ls.poodll.com/transcribe';
                           break;
                       case 'sydney':
                           $testitem->asrurl = 'https://sydney.ls.poodll.com/transcribe';
                           break;
                       case 'useast1':
                       default:
                           $testitem->asrurl = 'https://useast.ls.poodll.com/transcribe';
                   }
                   $testitem->maxtime = 15000;
                    break;

                case constants::TYPE_MULTICHOICE:
                case constants::TYPE_PAGE:
                case constants::TYPE_TEACHERTOOLS:
                case constants::TYPE_SHORTANSWER:
            }

            $testitems[]=$testitem;
        }

        return $testitems;
    }

    /* called from ajaxhelper to grade test */
    public function grade_test($answers){

        $items = $this->fetch_items();
        $currentitem=0;
        $score=0;
        foreach($items as $item) {
            $currentitem++;
            if (isset($answers->{'' . $currentitem})) {
                if ($item->correctanswer == $answers->{'' . $currentitem}) {
                    $score++;
                }
            }
        }
        if($score==0 || count($items)==0){
            return 0;
        }else{
            return floor(100 * $score/count($items));
        }
    }


}//end of class