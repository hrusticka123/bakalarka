<?php

//function that receives all groups of email IDs of specific user
//it returns parsed emails view, as we can see on the webpage, grouped by references, sorted and separated by time
function getmail($groups, $user)
{
    $return = new stdClass();
    $return->success = true;
    //groups of emails
    $return->groups = array();

    $Parser = new PhpMimeMailParser\Parser();

    //saves current time
    $today = new DateTime();
    $today->setTime( 0, 0, 0 );
    $headerdate = '';

    $i = 0;
    foreach ($groups as $group)
    {
        $path = getPathFromHash(end($group));
        $Parser->setPath(maildir."/".$user."/".$path."/".end($group));
        //first, we have to parse most recent date information for each group because of the headers above the group
        //so we parse the last email in the group
        $match_date = new DateTime();
        $puredate = explode(" (", $Parser->getHeader("date"))[0]; 
        $match_date = DateTime::createFromFormat( "D, d M Y H:i:s O", $puredate);

        $match_date->setTime( 0, 0, 0 );

        //compute the difference between current date and parsed date
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
        //simply write date
            if ($headerdate != date("d.m.Y",strtotime($Parser->getHeader("date"))))
            {
                $headerdate = date("d.m.Y",strtotime($Parser->getHeader("date")));
                $return->groups[$i] = new stdClass();
                $return->groups[$i]->header = $headerdate;
                $i++;
            }
        }

        $j = 0;
        //each groups email
        foreach ($group as $id)
        {
            $path = getPathFromHash($id);
            $mail = file_get_contents(maildir."/".$user."/".$path."/".$id);
            //parsing specific email data for view
            if ($mail)
            {
                $InnerParser = new PhpMimeMailParser\Parser();
                $InnerParser->setText($mail);
                $tags = json_decode(file_get_contents(maildir."/".$user."/".$path."/".$id.".tags")); 
                //first email of the group is not changing and initiated the conversation
                //whole group mail information, such as subject, are retrieved from him
                if ($j == 0)
                {
                    $return->groups[$i] = new stdClass();
                    $return->groups[$i]->emails = array();
                    $return->groups[$i]->tags = array();
                    $return->groups[$i]->ids = array();
                    $return->groups[$i]->subject = $InnerParser->getHeader("subject");
                    $return->groups[$i]->id = $id;
                }

                //check for new tags in group
                foreach ($tags as $newtag)
                {
                    if(!in_array($newtag,$return->groups[$i]->tags))
                        $return->groups[$i]->tags[] = $newtag;
                }

                //specific data for each message
                $return->groups[$i]->emails[$j] = new stdClass();
                $return->groups[$i]->emails[$j]->id = $id;
                $return->groups[$i]->ids[] = $id;
                $return->groups[$i]->emails[$j]->messageid = $InnerParser->getHeader("message-id");

                //references of each mail
                if ($InnerParser->getHeader("references"))
                {
                    $return->groups[$i]->emails[$j]->references = array();
                    $refs = preg_split('/(\s+|,)/', $InnerParser->getHeader("references"));
                    foreach ($refs as $ref)
                    {
                        $ref = preg_replace('/\s+/', '', $ref);
                        $return->groups[$i]->emails[$j]->references[] = $ref;
                    }
                }

                //more data about email
                $return->groups[$i]->emails[$j]->from= $InnerParser->getHeader("from");
                $return->groups[$i]->emails[$j]->to = $InnerParser->getHeader("to");
                $return->groups[$i]->emails[$j]->date = $InnerParser->getHeader("date");
                //computed preview of message
                $preview = (strlen($InnerParser->getMessageBody("text")) > 10) ? mb_substr($InnerParser->getMessageBody("text"),0,10)."..." : mb_substr($InnerParser->getMessageBody("text"),0,strlen($InnerParser->getMessageBody("text")));
                $return->groups[$i]->emails[$j]->preview = $preview;
                //if mail does not have HTML body
                if (strpos($InnerParser->getHeader("content-type"),'text/plain') !== false)
                {
                    $return->groups[$i]->emails[$j]->type = 'text';
                    $return->groups[$i]->emails[$j]->text = wordwrap($InnerParser->getMessageBody("text"),80);

                }
                else
                {
                    $return->groups[$i]->emails[$j]->type = 'html';
                    $return->groups[$i]->emails[$j]->text = $InnerParser->getMessageBody("html");
                }

                $attachments = $InnerParser->getAttachments();
                $atts = array();
                //show attachments as well
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

?>
