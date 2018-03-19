<?php

if ( $_SERVER['REQUEST_METHOD'] == 'GET' && realpath(__FILE__) == realpath( $_SERVER['SCRIPT_FILENAME'] ) ) 
{
    header( 'HTTP/1.0 403 Forbidden', TRUE, 403 );
}

class DBElastic
{
    private $hostname;
    private $username;
    private $password;

    public function DBElastic($hostname, $username, $password)
    {
        $this->hostname = $hostname;
        $this->username = $username;
        $this->password = $password;
    }

    public function SaveMail()
    {
        $inbox = imap_open($this->hostname,$this->username,$this->password) or die('Cannot connect to Gmail: ' . imap_last_error());

        if (!file_exists('/emailsdb/'.$this->username))
            mkdir('/emailsdb/'.$this->username , 0777, true);
        
        $emails_count = imap_num_msg($inbox);

        if ($emails_count > 0)
        {   
            for ($i = $emails_count; $i > $emails_count-5 && $i >= 0; $i--)
            {
                $meta = imap_fetch_overview($inbox, $i)[0];
                $meta->subject = imap_utf8($meta->subject);

                $id = $this->SaveToDB($inbox, $i, $meta);
                $this->SaveToElastic($meta, $id);
            }
        }
        else
            return json_decode ("{}");

        imap_close($inbox);
    }

    private function SaveToDB($inbox, $i,$meta)
    {
        $headers = imap_fetchheader($inbox, $i, FT_PREFETCHTEXT);
        $body = imap_body($inbox, $i);

        $id = hash("sha256",$body);

        if (!file_exists('/emailsdb/'.$this->username.'/'.$id.".eml") && !file_exists('/emailsdb/'.$this->username.'/'.$id.".mtd"))
        {
            file_put_contents('/emailsdb/'.$this->username.'/'.$id.".mtd", json_encode($meta));
            file_put_contents('/emailsdb/'.$this->username.'/'.$id.".eml", $headers.'\n'.$body);
        }

        return $id;
    }

    private function SaveToElastic($meta, $id)
    {
        $req = curl_init();

        $data = [
            "to" => $meta->to,
            "from" => $meta->from,
            "subject" => $meta->subject,
            "date" => $meta->date,
            "seen" => $meta->seen
        ];
        
        curl_setopt_array($req, [
            CURLOPT_URL            => "http://localhost/es/emailsdb/".$this->username."/".$id."?pretty",
            CURLOPT_CUSTOMREQUEST  => "POST",
            CURLOPT_POSTFIELDS     => json_encode($data),
            CURLOPT_HTTPHEADER     => [ "Content-Type: application/json" ],
            CURLOPT_RETURNTRANSFER => true,
        ]);
        
        $response = curl_exec($req);

        curl_close($req);
    }

    public function GetHeaders($count)
    {
        $retMeta = [];

        foreach (glob('/emailsdb/'.$this->username.'/*.mtd') as $file) 
        {
           $retMeta[] = json_decode(file_get_contents($file))->subject;
        }

        return json_encode($retMeta);
    }
}

?>