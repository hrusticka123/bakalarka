<?php


function getmail($groups, $user)
{
    $config = require './config.php';
    
    $return = new stdClass();
    $return->success = true;
    $return->groups = array();

    $Parser = new PhpMimeMailParser\Parser();

    $today = new DateTime();
    $today->setTime( 0, 0, 0 );
    $headerdate = '';

    $i = 0;
    foreach ($groups as $group)
    {

        $j = 0;
        foreach ($group as $id)
        {
            $mail = file_get_contents($config['maildir']."/".$user."/".$id."/".$id);
            if ($mail)
            {
                $Parser->setText($mail);
                $tags = json_decode(file_get_contents($config['maildir']."/".$user."/".$id."/".$id.".tags")); 
                if ($j == 0)
                { 
                    $match_date = new DateTime();
                    $match_date = DateTime::createFromFormat( "D, d M Y H:i:s O", $Parser->getHeader("date"));
                    $match_date->setTime( 0, 0, 0 );

                    $diff = $today->diff( $match_date );
                    $diffDays = (integer)$diff->format( "%R%a" );

                    switch( $diffDays ) {
                        case 0:
                            if ($headerdate != 'Today')
                            {
                                $headerdate = 'Today';
                                $return->groups[$i] = new stdClass();
                                $return->groups[$i]->header = $headerdate;
                                $i++;
                            }
                            break;
                        case -1:
                            if ($headerdate != "Yesterday")
                            {
                                $headerdate = "Yesterday";
                                $return->groups[$i] = new stdClass();
                                $return->groups[$i]->header = $headerdate;
                                $i++;
                            }
                            break;
                        default:
                            if ($headerdate != date("d.m.Y",strtotime($date)))
                            {
                                $headerdate = date("d.m.Y",strtotime($date));
                                $return->groups[$i] = new stdClass();
                                $return->groups[$i]->header = $headerdate;
                                $i++;
                            }
                    }

                    $return->groups[$i] = new stdClass();
                    $return->groups[$i]->emails = array();
                    $return->groups[$i]->tags = array();
                    $return->groups[$i]->ids = array();
                    $return->groups[$i]->subject = $Parser->getHeader("subject");
                    $return->groups[$i]->id = $id;
                }

                foreach ($tags as $newtag)
                {
                    if(!in_array($newtag,$return->groups[$i]->tags))
                        $return->groups[$i]->tags[] = $newtag;
                }

                $return->groups[$i]->emails[$j] = new stdClass();
                $return->groups[$i]->emails[$j]->id = $id;
                $return->groups[$i]->ids[] = $id;
                $return->groups[$i]->emails[$j]->messageid = $Parser->getHeader("message-id");

                if ($Parser->getHeader("references"))
                {
                    $return->groups[$i]->emails[$j]->references = array();
                    $refs = preg_split('/(\s+|,)/', $Parser->getHeader("references"));
                    foreach ($refs as $ref)
                    {
                        $ref = preg_replace('/\s+/', '', $ref);
                        $return->groups[$i]->emails[$j]->references[] = $ref;
                    }
                }

                $return->groups[$i]->emails[$j]->from= $Parser->getHeader("from");
                $return->groups[$i]->emails[$j]->to = $Parser->getHeader("to");
                $return->groups[$i]->emails[$j]->date = $Parser->getHeader("date");
                
                $preview = (strlen($Parser->getMessageBody("text")) > 10) ? mb_substr($Parser->getMessageBody("text"),0,10)."..." : mb_substr($Parser->getMessageBody("text"),0,strlen($Parser->getMessageBody("text")));
                $return->groups[$i]->emails[$j]->preview = $preview;
                $htmlbody = $Parser->getMessageBody("html");
                if ($htmlbody != "")
                    $return->groups[$i]->emails[$j]->html = $htmlbody; 

                $attachments = $Parser->getAttachments();
                $atts = array();
                foreach($attachments as $att)
                    $atts[] = $att->getFilename();
                $return->groups[$i]->emails[$j]->atts = $atts;

            }
            else
                return '{"success" : false}';
            $j++;
        }
        $i++;
    }
    
    return json_encode($return);
}

function getName($att)
{
    $mimestring = $att->getMimePartStr();
  $start = strpos($mimestring,"filename=")+strlen("filename=")+1;
  $end = strpos($mimestring, "\"", $start);
  return substr($mimestring, $start, $end - $start);
}

?>
