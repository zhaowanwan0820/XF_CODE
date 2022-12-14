<?php

/**
 * Rundeck server
 */
class Rundeck extends JobServer
{
    /**
     * Rundeck token, generate in user -> profile -> generate token
     */
    var $token = "";

    /**
     * Project name
     */
    var $project = "";

    /**
     * Local url, 4440 is a default rundeck port
     * @var string
     */
    var $url = "http://localhost:4440";
    var $jobs = null; // cache for one invocation, pretty slow

    /**
     * PHP Path
     * @var string
     */
    public $phpPath = "";

    /**
     * Yiic Path
     * @var string
     */
    public $yiicPath = "";

    function __construct()
    {
    }

    public function init()
    {
        return true;
    }

    function xml($url, $post = null)
    {
        if (!$this->token) {
            throw new Exception("Rundeck requires token!");
        }

        if (!$this->project) {
            throw new Exception("Rundeck requires project name!");
        }

        $url = $this->url . $url;
        if (strstr($url, "?")) {
            $url .= "&authtoken=" . $this->token;
        } else {
            $url .= "?authtoken=" . $this->token;
        }

        $options = [
            "http" => []
        ];
        $options["http"]["method"] = $post != null ? "POST" : "GET";
        if ($post) {
            $options["http"]["header"] = "Content-Type: application/x-www-form-urlencoded";
            $options["http"]["content"] = $post;
        }
        $ctx = stream_context_create($options);
        $this->log("Invoking " . $url);
        $out = file_get_contents($url, false, $ctx);
        if ($out === false) {
            throw new Exception("HTTP request failed to " . $url . "!");
        }

        $dom = new DOMDocument();
        $dom->loadXML($out);
        $this->json_prepare_xml($dom);
        $xml = $dom->saveXML();
        // error_log($xml);
        $out = simplexml_load_string($xml);
        $out = json_decode(json_encode($out));
        return $out;
    }

    /// Move all attributes to elements -> lets hope on <xxxx attr="val">dsds</xxxx> constructions
    function json_prepare_xml($domNode)
    {
        $texts = [];
        $renames = [];
        foreach ($domNode->childNodes as $node) {
            if ($node->nodeType == XML_ELEMENT_NODE) {
                $this->json_prepare_xml($node);
                if (strstr($node->nodeName, "-")) {
                    $renames[] = $node;
                }
            } else {
                if ($node->nodeType == XML_CDATA_SECTION_NODE) {
                    $texts[] = $node;
                }
            }
        }

        foreach ($texts as $node) {
            $domNode->removeChild($node);
            $domNode->appendChild($domNode->ownerDocument->createTextNode($node->nodeValue));
        }

        if ($domNode->attributes) {

            while ($domNode->attributes->length) {
                $attr = $domNode->attributes->item(0);
                // nodeValue is already all entities have processed, but on createElement - need to escape
                $val = htmlspecialchars($attr->nodeValue);
                $domNode->appendChild($domNode->ownerDocument->createElement($attr->nodeName, $val));
                $domNode->removeAttributeNode($attr);
            }
        }

        foreach ($renames as $node) {
            // capitalize next letter
            $nn = explode("-", $node->nodeName);
            $s = "";
            foreach ($nn as $n) {
                if ($s != "") {
                    $n = strtoupper(substr($n, 0, 1)) . substr($n, 1);
                }
                $s .= $n;
            }
            $nn = $node->ownerDocument->createElement($s);
            foreach ($node->childNodes as $subnode) {
                $nn->appendChild($subnode);
                //$node->removeChild($subnode);
            }
            $domNode->removeChild($node);
            $domNode->appendChild($nn);
        }
    }

    function jobs($cache = true)
    {
        if ($this->jobs && $cache) {
            return $this->jobs;
        }

        $out = $this->xml("/api/2/project/" . $this->project . "/jobs");
        $jobs = [];
        if ($out->jobs && $out->jobs->job) {
            if (is_array($out->jobs->job)) {
                $jj = $out->jobs->job;
                for ($i = 0; $i < count($jj); $i++) {
                    $job = new Job($jj[$i]->name);
                    $job->id = $jj[$i]->id;
                    $jobs[] = $job;
                }
            } else {
                $job = new Job($out->jobs->job->name);
                $job->id = $out->jobs->job->id;
                $jobs[] = $job;
            }
        }

        $this->jobs = $jobs;
        return $jobs;
    }

    function find($name, $fail = true)
    {
        $jobs = $this->jobs();
        $job = null;
        for ($i = 0; $i < count($jobs); $i++) {
            if ($jobs[$i]->name == $name) {
                $job = $jobs[$i];
                break;
            }
        }
        if ($job == null && $fail) {
            throw new Exception("Job not found: " . $name);
        }
        return $job;
    }

    function job($job)
    {
        $job = is_string($job) ? $this->find($job) : $job;
        $out = $this->xml("/api/1/job/" . $job->id);

        $j = $out->job;
        if (isset($j->sequence) && isset($j->sequence->command)) {
            $c = $j->sequence->command;
            $c = is_array($c) ? $c : [$c];
            for ($i = 0; $i < count($c); $i++) {
                $cc = $c[$i];
                if (isset($cc->exec)) {
                    $job->exec[] = $cc->exec;
                } else
                    if (isset($cc->script)) {
                        $job->exec[] = $cc->script;
                    }
            }
        }

        if (isset($j->schedule)) {
            $pat = [];
            $sec = $j->schedule->time->seconds;
            if ($sec != 0) {
                throw new Exception("Specific second is not supported!");
            }

            // Your average crontab mask
            $pat[] = $j->schedule->time->minute;
            $pat[] = $j->schedule->time->hour;
            $pat[] = isset($j->schedule->month->day) ? $j->schedule->month->day : "*";
            $pat[] = $j->schedule->month->month;
            if (isset($j->schedule->weekday) && isset($j->schedule->weekday->day)) {
                $pat[] = $j->schedule->weekday->day;
            } else {
                $pat[] = "*";
            }
            $pat[] = $j->schedule->year->year;

            $job->schedule = join(" ", $pat);
        }

        if (isset($j->notification) && isset($j->notification->onsuccess) && $j->notification->onsuccess->webhook) {
            $urls = explode(",", $j->notification->onsuccess->webhook->urls);
            $job->notifySuccess = $urls;
        }

        if (isset($j->notification) && isset($j->notification->onfailure) && $j->notification->onfailure->webhook) {
            $urls = explode(",", $j->notification->onfailure->webhook->urls);
            $job->notifyFailure = $urls;
        }

        if (isset($j->notification) && isset($j->notification->onstart) && $j->notification->onstart->webhook) {
            $urls = explode(",", $j->notification->onstart->webhook->urls);
            $job->notifyStart = $urls;
        }

        if (isset($j->description) && is_string($j->description)) {
            $job->description = $j->description;
        }

        return $job;
    }

    function createXML($job)
    {
        $xml = "";
        $xml .= "<joblist>";
        $xml .= "<job>";

        if ($job->schedule) {
            $xml .= "<schedule>";
            list ($min, $hour, $dayofmonth, $month, $dayofweek, $year) = explode(" ", $job->schedule);
            $xml .= "<time seconds='0' minute='" . $min . "' hour='" . $hour . "' />";
            if ($dayofmonth == "*") {
                $dayofweek = "?"; // these are exclusive in rundeck
            }
            if ($dayofweek != "*") {
                $xml .= "<weekday day='" . $dayofweek . "' />";
            } else {
                $xml .= "<dayofmonth/>";
            }
            $xml .= "<month month='" . $month . "' day='" . $dayofmonth . "'/>";
            $xml .= "<year year='" . $year . "' />";
            $xml .= "</schedule>";
        }

        $xml .= "<loglevel>INFO</loglevel>";
        $xml .= "<sequence keepgoing='false' strategy='node-first'>";

        foreach ($job->exec as $exec) {
            $xml .= "<command>";
            if (strstr($exec, "\n")) {
                $xml .= "<scriptargs />";
                $xml .= "<script><![CDATA[";
                $exec = explode("\n", $exec);
                foreach ($exec as $line) {
                    $xml .= $line . "\n";
                }
                $xml .= "]]></script>";
            } else {
                $xml .= "<exec>" . $exec . "</exec>";
            }
            $xml .= "</command>";
        }
        $xml .= "</sequence>";
        $xml .= "<description>" . (isset($job->description) ? $job->description : "") . "</description>";
        $xml .= "<name>" . $job->name . "</name>";
        $xml .= "<context>";
        $xml .= "<project>" . $this->project . "</project>";
        $xml .= "</context>";

        if ((count($job->notifyStart) + count($job->notifySuccess) + count($job->notifyFailure)) > 0) {
            $xml .= "<notification>";

            $emails = join(",", $job->notifyStart);
            if ($emails) {
                $xml .= "<onstart>";
                $xml .= "<email recipients='{$emails}' />";
                $xml .= "</onstart>";
            }

            $emails = join(",", $job->notifyFailure);
            if ($emails) {
                $xml .= "<onfailure>";
                $xml .= "<email recipients='{$emails}' />";
                $xml .= "</onfailure>";
            }

            $emails = join(",", $job->notifySuccess);
            if ($emails) {
                $xml .= "<onsuccess>";
                $xml .= "<email recipients='{$emails}' />";
                $xml .= "</onsuccess>";
            }

            $xml .= "</notification>";
        }

        $xml .= "</job>";
        $xml .= "</joblist>";
        return $xml;
    }

    function create($job)
    {
        if (isset($job->id)) {
            throw new Exception("Job already have id: " . $job->id . ", refusing to register!");
        }

        $xml = $this->createXML($job);
        // Send it as xmlBatch POST field
        $xml = "xmlBatch=" . urlencode($xml);
        $out = $this->xml("/api/1/jobs/import", $xml);
        Yii::log(json_encode($out, true), CLogger::LEVEL_INFO, __CLASS__);
        if ($out && $out->succeeded && $out->succeeded->job) {
            $job->id = $out->succeeded->job->id;
        } else {
            throw new Exception("Job creation failed!");
        }
        return $job;
    }

    function run($job)
    {
        $job = is_string($job) ? $this->find($job) : $job;
        if (!$job->id) {
            throw new Exception("Job have no id!");
        }
        $out = $this->xml("/api/1/job/" . $job->id . "/run");
        if ($out->success != "true") {
            throw new Exception("Execution failed");
        }
        return $this->parseExecution($out->executions->execution);
    }

    function last($job, $count = 5)
    {
        $job = is_string($job) ? $this->find($job) : $job;
        if (!$job->id) {
            throw new Exception("Job have no id!");
        }

        $out = $this->xml("/api/1/job/" . $job->id . "/executions?max=" . $count);

        $l = [];
        if (isset($out->executions) && isset($out->executions->execution)) {
            $ee = $out->executions->execution;
            $ee = is_array($ee) ? $ee : [$ee];
            foreach ($ee as $exec) {
                $e = $this->parseExecution($exec);
                $l[] = $e;
            }
        }

        $ll = [];
        //for ($i = count($l) - 1; $i >= 0; $i --) {
        for ($i = 0; $i < count($l); $i++) {
            $exec = $l[$i];
            if ($exec->status != "running") {
                $out = $this->xml("/api/10/execution/" . $exec->id . "/output/state");
                if ($out->output && $out->output->entries && $out->output->entries->entry) {
                    $log = $out->output->entries->entry;
                    $log = is_array($log) ? $log : [$log];
                    foreach ($log as $entry) {
                        if ($entry->type == "log") {
                            $exec->log[] = $entry->log;
                        }
                    }
                }
            }
            $ll[] = $exec;
        }
        return $ll;
    }

    function parseExecution($exec)
    {
        $e = new JobExecution();
        $e->job = $exec->job->name;
        $e->id = $exec->id;
        $e->started = $this->dtunix(date_parse($exec->dateStarted));
        if (isset($exec->dateEnded)) {
            $e->ended = $this->dtunix(date_parse($exec->dateEnded));
        }
        $st = $exec->status;
        if ($st == "succeeded") {
            $st = "complete";
        }
        $e->status = $st;
        return $e;
    }

    function dtunix($parsed)
    {
        return mktime(
            $parsed['hour'],
            $parsed['minute'],
            $parsed['second'],
            $parsed['month'],
            $parsed['day'],
            $parsed['year']
        );
    }

    function status($job)
    {
        $l = $this->last($job);
        if (count($l) > 0) {
            return $l[0]->status;
        } else {
            return null;
        }
    }

    function delete($job)
    {
        $job = is_string($job) ? $this->find($job) : $job;
        if (!$job->id) {
            throw new Exception("Job have no id!");
        }

        $out = $this->xml("/api/5/jobs/delete", "idlist=" . $job->id);
        if (!$out->deleteJobs->allsuccessful) {
            throw new Exception("Job " . $job->id . " delete failed");
        }
    }
}
