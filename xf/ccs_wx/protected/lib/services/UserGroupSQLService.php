<?php

class UserGroupSQLService extends ItzInstanceService
{
    const FULL_QUERY = true;
    const TRIAL_QUERY = false;
    private $commonDictionary = [
        'userInfo' => ['phone_status', 'real_status', 'email_status', 'reg_device', 'addtime_start', 'addtime_end', 'status'],
        'channelInfo' => ['channel_waytype', 'channel_ups_id', 'channel_id'],
        'rechargeInfo' => ['is_recharge', 'recharge_time_start', 'recharge_time_end', 'recharge_type'],
        'investInfo' => ['isinvested', 'borrow_id', 'invest_addtime_start', 'invest_addtime_end'],
        'postBonusInfo' => ['post_user_distinct', 'post_time_start', 'post_time_end', 'post_subject', 'post_text_size', 'post_replies_num', 'post_replies_num'],
        'repliesBonusInfo' => ['replies_post_id', 'replies_time_start', 'replies_time_end', 'replies_floor', 'replies_keywords', 'replies_user_distinct'],
        'voteBonusInfo' => ['vote_id', 'vote_time_start', 'vote_time_end', 'vote_select', 'vote_user_distinct'],
        'field' => ['A' => ['dw_user.user_id', 'dw_user.phone', 'dw_user.username', 'dw_user.realname', 'dw_user.sex', 'dw_user.email', 'dw_user.addtime'], 'B' => ['dw_user.user_id', 'dw_user.phone', 'dw_user.username', 'dw_user.realname', 'dw_user.sex', 'dw_user.email', 'dw_user.addtime'], 'C' => ['dw_user.user_id', 'dw_user.phone', 'dw_user.username', 'dw_user.realname', 'dw_user.sex', 'dw_user.email', 'dw_user.addtime'], 'D' => ['dw_user.user_id', 'dw_user.phone', 'dw_user.username', 'dw_user.realname', 'dw_user.sex', 'dw_user.email', 'dw_user.addtime'], 'E' => ['itz_forum_thread.authorid'], 'F' => ['itz_forum_post.authorid'], 'G' => ['itz_forum_pollvoter.uid'], 'AB' => ['dw_user.user_id', 'dw_user.phone', 'dw_user.username', 'dw_user.realname', 'dw_user.sex', 'dw_user.email', 'dw_user.addtime']]
    ];
    private $sql;

    public function buildMasterSQL($condition, $type = false)
    {
        $condition = $this->hack($condition);

        $level = $this->typeIdentification($condition);
        if ($type == false) {
            $this->addCondition('SELECT', 'count(DISTINCT dw_user.user_id)');
        } else {
            $tempField = [];
            // $condition['field'] = $this->commonDictionary['field'][$level];
            if(isset($condition['field'])) {
                foreach ($condition['field'] as $key => $value) {
                    switch ($value) {
                        case 'user_id':
                            in_array($level, ['A', 'AB', 'ABC', 'ABCD', 'AC', 'ACD', 'AD', 'ABD']) && $tempField[] = 'distinct dw_user.user_id';
                            break;
                        case 'phone':
                            in_array($level, ['A', 'AB', 'ABC', 'ABCD', 'AC', 'ACD', 'AD', 'ABD']) && $tempField[] = 'dw_user.phone';
                            break;
                        case 'username':
                            in_array($level, ['A', 'AB', 'ABC', 'ABCD', 'AC', 'ACD', 'AD', 'ABD']) && $tempField[] = 'dw_user.username';
                            break;
                        case 'realname':
                            in_array($level, ['A', 'AB', 'ABC', 'ABCD', 'AC', 'ACD', 'AD', 'ABD']) && $tempField[] = 'dw_user.realname';
                            break;
                        case 'sex':
                            in_array($level, ['A', 'AB', 'ABC', 'ABCD', 'AC', 'ACD', 'AD', 'ABD']) && $tempField[] = 'dw_user.sex';
                            break;
                        case 'email':
                            in_array($level, ['A', 'AB', 'ABC', 'ABCD', 'AC', 'ACD', 'AD', 'ABD']) && $tempField[] = 'dw_user.email';
                            break;
                        case 'addtime':
                            in_array($level, ['A', 'AB', 'ABC', 'ABCD', 'AC', 'ACD', 'AD', 'ABD']) && $tempField[] = 'dw_user.addtime';
                            break;
                        default:
                            break;
                    }
                }
            }
            $this->addCondition('SELECT', implode(',', $tempField));
        }
        switch ($level) {
            case 'A':
                $this->addCondition('FROM', 'dw_user');
                $this->addCondition('WHERE', '');
                $this->SQLA($condition);
                // var_dump('sadsad');
                break;
            case 'AB':
                $this->addCondition('FROM', 'dw_user, itz_channel_log');
                $this->addCondition('WHERE', '');
                $this->addCondition('dw_user.user_id|=', 'itz_channel_log.user_id/', 'and');
                $this->SQLA($condition);
                $this->SQLB($condition);
                break;
            case 'ABC':
                $this->addCondition('FROM', 'dw_user, itz_channel_log');
                $this->addCondition('WHERE', '');
                $this->addCondition('dw_user.user_id|=', 'itz_channel_log.user_id/', 'and');
                $this->SQLA($condition);
                $this->SQLB($condition);
                $sql = $this->SQLC($condition);
                break;
            case 'ABD':
                $this->addCondition('FROM', 'dw_user, itz_channel_log');
                $this->addCondition('WHERE', '');
                $this->addCondition('dw_user.user_id|=', 'itz_channel_log.user_id/', 'and');
                $this->SQLA($condition);
                $this->SQLB($condition);
                $sql = $this->SQLD($condition);
                break;
            case 'ABCD':
                $this->addCondition('FROM', 'dw_user, itz_channel_log');
                $this->addCondition('WHERE', '');
                $this->addCondition('dw_user.user_id|=', 'itz_channel_log.user_id/', 'and');
                $this->SQLA($condition);
                $this->SQLB($condition);
                $sql = $this->SQLC($condition);
                $this->SQLD($condition);
                break;
            case 'AC':
                $this->addCondition('FROM', 'dw_user');
                $this->addCondition('WHERE', '');
                $this->SQLA($condition);
                $this->SQLC($condition);
                break;
            case 'ACD':
                $this->addCondition('FROM', 'dw_user');
                $this->addCondition('WHERE', '');
                $this->SQLA($condition);
                $this->SQLC($condition);
                $this->SQLD($condition);
                break;
            case 'AD':
                $this->addCondition('FROM', 'dw_user');
                $this->addCondition('WHERE', '');
                $this->SQLA($condition);
                $this->SQLD($condition);
                break;
        }
        // var_dump($this->build($this->sql));
        return $this;
    }

    public function build($sql = false)
    {
        $sql || $sql = $this->sql;
        $temp = [];
        foreach ($sql as $key => $value) {
            switch (strtolower($key)) {
                case 'select':
                    $temp[0] = strtoupper($key);
                    $temp[1] = $value;
                    break;
                case 'from':
                    $temp[2] = strtoupper($key);
                    $temp[3] = $value;
                    break;
                case 'where':
                    $temp[4] = strtoupper($key);
                default:
                    break;
            }
            $count = 5;
            // var_dump($value);
            if (strtolower($key) == 'and') {
                $flag = 0;
                foreach ($value as $kk => $vv) {
                    if(strpos($vv, '/') !== false) {
                        if (rtrim($vv, '/') == false) {
                            continue;
                        }
                    } else {
                        if($vv === false) {
                            continue;
                        }
                    }
                    $flag++;
                    if ($flag > 1) {
                        $temp[$count++] = 'and';
                    }
                    if (explode('|', $kk)) {
                        $temp[$count++] = implode('', explode('|', $kk));
                    } else {
                        $temp[$count++] = $kk;
                    }
                    if (strpos($vv, '/')) {
                        $temp[$count++] = rtrim($vv, '/');
                    } else {
                        $temp[$count++] = "'{$vv}'";
                    }
                    // var_dump($vv);
                }
            }
            if(strtolower($key) == 'limit') {
                $temp[] = $key;
                $temp[] = $value;
            }
            // var_dump($value);
        }
        ksort($temp);
        return (implode(' ', $temp));
    }

    public function buildBBSSQL($condition, $type = false)
    {
        $level = $this->typeIdentification($condition);
        if ($type == false) {
            $this->addCondition('SELECT', 'count(distinct tid)');
        } else {
            $this->addCondition('SELECT', implode(',', $this->commonDictionary['field'][$level]));
        }
        switch ($level) {
            case 'E':
                $this->addCondition('SELECT', 'count(distinct itz_forum_thread.tid)');
                $this->addCondition('FROM', 'itz_forum_thread, itz_forum_post');
                $this->addCondition('WHERE', '');
                $this->addCondition('itz_forum_thread.tid|=', 'itz_forum_post.tid/', 'and');
                $this->addCondition('itz_forum_thread.`subject`| LIKE', $this->postSubject($condition['post_subject']), 'and');
                $this->addCondition('itz_forum_post.dateline|>=', $this->postTimeStart($condition['post_time_start']), 'and');
                $this->addCondition('itz_forum_post.dateline|<=', $this->postTimeEnd($condition['post_time_end']), 'and');
                $this->addCondition('itz_forum_post.first|=', '1', 'and');
                $replies = $this->postReplies($condition);
                $this->addCondition(current($replies), end($replies) . '/', 'and');
                $postBonusInfoMessageSubQuery = $this->postBonusInfoMessageSubQuery($condition['post_text_size']);
                $this->addCondition(current($postBonusInfoMessageSubQuery), end($postBonusInfoMessageSubQuery) . '/', 'and');
                $field = $this->postUserDistinct($condition['post_user_distinct']);
                $type && $field && $this->addCondition(current($field), end($field));
                !$type && $field && $this->addCondition(current($field), 'count(' . end($field) . ')');
                break;
            case 'F':
                $this->addCondition('SELECT', 'count(distinct itz_forum_post.tid)');
                $this->addCondition('FROM', 'itz_forum_post');
                $this->addCondition('WHERE', '');
                $this->addCondition('itz_forum_post.tid|=', $this->repliesInfoPostId($condition['replies_post_id']), 'and');
                $this->addCondition('itz_forum_post.dateline|>=', $this->repliesInfoTimeStart($condition['replies_time_start']), 'and');
                $this->addCondition('itz_forum_post.dateline|<=', $this->repliesInfoTimeEnd($condition['replies_time_end']), 'and');
                $floorStart = $this->repliesInfoFloor($condition['replies_floor']);
                $this->addCondition(current($floorStart), end($floorStart), 'and');
                $this->addCondition('itz_forum_post.position |<=', $this->repliesInfoEndFloor($condition['replies_end_floor']), 'and');
                $this->addCondition('itz_forum_post.message| LIKE', $this->repliesInfoKeywords($condition['replies_keywords']), 'and');
                $field = $this->repliesInfoUserDistinct($condition['replies_user_distinct']);
                $type && $field && $this->addCondition(current($field), end($field));
                !$type && $field && $this->addCondition(current($field), 'count(' . end($field) . ')');
                // var_dump($condition);
                break;
            case 'G':
                $this->addCondition('FROM', 'itz_forum_pollvoter');
                $this->addCondition('WHERE', '');
                $this->addCondition('itz_forum_pollvoter.tid|=', $this->voteBonusInfoTid($condition['vote_id']), 'and');
                $this->addCondition('itz_forum_pollvoter.options|=', $this->voteBonusInfoOptions($condition['vote_select']), 'and');
                $this->addCondition('itz_forum_pollvoter.dateline|>=', $this->voteBonusInfoDateLineStart($condition['vote_time_start']), 'and');
                $this->addCondition('itz_forum_pollvoter.dateline |<=', $this->voteBonusInfoDateLineEnd($condition['vote_time_end']), 'and');
                $field = $this->voteBonusInfoUserDistinct($condition['vote_user_distinct']);
                $type && $field && $this->addCondition(current($field), end($field));
                !$type && $field && $this->addCondition(current($field), 'count(' . end($field) . ')');
                break;
        }
        return $this;
    }

    public function addCondition($field, $value, $type = false)
    {
        if (!$type) {
            $this->sql[$field] = $value;
        }
        if ($type == 'and') {
            $this->sql[$type][$field] = $value;
        }
        return $this;
    }

    public function typeIdentification($condition)
    {
        $levels = [];
        $this->userInfo($condition) && $levels[] = 'A';
        $this->channelInfo($condition) && $levels[] = 'B';
        $this->rechargeInfo($condition) && $levels[] = 'C';
        $this->investInfo($condition) && $levels[] = 'D';
        $this->postBonusInfo($condition) && $levels = ['E'];
        $this->repliesBonusInfo($condition) && $levels = ['F'];
        $this->voteBonusInfo($condition) && $levels = ['G'];
        // var_dump(implode('', $levels));
        return implode('', $levels);
    }

    private function userInfo($condition)
    {
        foreach ($this->commonDictionary[__FUNCTION__] as $value) {
            if (array_key_exists($value, $condition) && $condition[$value] !== 'false') {
                return true;
            }
        }
        return false;
    }

    private function channelInfo($condition)
    {
        foreach ($this->commonDictionary[__FUNCTION__] as $value) {
            if (array_key_exists($value, $condition) && $condition[$value] !== 'false') {
                return true;
            }
        }
        return false;
    }

    private function rechargeInfo($condition)
    {
        foreach ($this->commonDictionary[__FUNCTION__] as $value) {
            if (array_key_exists($value, $condition) && $condition[$value] !== 'false') {
                return true;
            }
        }
        return false;

    }

    private function investInfo($condition)
    {
        foreach ($this->commonDictionary[__FUNCTION__] as $value) {
            if (array_key_exists($value, $condition) && $condition[$value] !== 'false') {
                return true;
            }
        }
        return false;
    }

    private function postBonusInfo($condition)
    {
        foreach ($this->commonDictionary[__FUNCTION__] as $value) {
            if (array_key_exists($value, $condition) && $condition[$value] !== 'false') {
                return true;
            }
        }
        return false;
    }

    private function repliesBonusInfo($condition)
    {
        foreach ($this->commonDictionary[__FUNCTION__] as $value) {
            if (array_key_exists($value, $condition) && $condition[$value] !== 'false') {
                return true;
            }
        }
        return false;
    }

    private function voteBonusInfo($condition)
    {
        foreach ($this->commonDictionary[__FUNCTION__] as $value) {
            if (array_key_exists($value, $condition) && $condition[$value] !== 'false') {
                return true;
            }
        }
        return false;
    }

    private function userInfoPhoneStatus($value)
    {
        switch ($value) {
            case '0':
                return 0;
                break;
            case '1':
                return 1;
                break;
        }
        return false;
    }

    private function userInfoRealStatus($value)
    {
        switch ($value) {
            case '0':
                return 0;
                break;
            case '1':
                return 1;
            case '2':
                return 2;
            case '3':
                return 3;
        }
        return false;
    }

    private function userInfoEmailStatus($value)
    {
        switch ($value) {
            case '0':
                return 0;
                break;
            case '1':
                return 1;
                break;
        }
        return false;
    }

    private function userInfoRegDevice($value)
    {
        switch ($value) {
            case 'pc':
                return 'pc';
                break;
            case 'wap':
                return 'wap';
                break;
            case 'ios':
                return 'ios';
                break;
            case 'android':
                return 'android';
                break;
        }
        return false;
    }

    private function userInfoAddtimeStart($value)
    {
        if ($value === 'false') {
            return false;
        }
        return $value;
    }

    private function userInfoAddtimeEnd($value)
    {
        if ($value === 'false') {
            return false;
        }
        return $value;
    }

    private function userInfoStatus($value)
    {
        switch ($value) {
            case '0':
                return 0;
                break;
            case '1':
                return 1;
                break;
            case '2':
                return 2;
                break;
        }
        return false;
    }

    private function rechargeInfoSubquery($value)
    {
        $temp['SELECT'] = 'user_id';
        $temp['FROM'] = 'dw_account_recharge';
        $temp['WHERE'] = '';
        $temp['and']['dw_user.user_id|='] = 'dw_account_recharge.user_id/';
        $temp['and']['addtime|>'] = $this->rechargeInfoTimeStart($value['recharge_time_start']);
        $temp['and']['addtime|<'] = $this->rechargeInfoTimeEnd($value['recharge_time_end']);
        $temp['and']['status|='] = '1';
        $temp['and']['type|='] = $this->rechargeInfoRechargeType($value['recharge_type']);
        $logic = $this->rechargeInfoIsRecharge($value['is_recharge']);
        if (!$logic) {
            return [false, false];
        }
        return [$logic, "(" . $this->build($temp) . ")"];
    }

    private function rechargeInfoIsRecharge($value)
    {
        switch ($value) {
            case '1':
                return 'EXISTS';
                break;
            case '2':
                return 'NOT EXISTS';
                break;
            default:
                break;
        }
        return false;
    }

    private function rechargeInfoTimeStart($value)
    {
        // var_dump($value);
        if ($value === 'false') {
            return false;
        }
        return $value;
    }

    private function rechargeInfoTimeEnd($value)
    {
        if ($value === 'false') {
            return false;
        }
        return $value;
    }

    private function rechargeInfoRechargeType($value)
    {
        switch ($value) {
            case '0':
                return 0;
                break;
            case '1':
                return 1;
            case '2':
                return 2;
            case '3':
                return 3;
                break;
            case '4':
                return 4;
                break;
            case '5':
                return 5;
                break;
            default:
                break;
        }
        return false;
    }

    private function investInfoSubQuery($value)
    {
        $temp['SELECT'] = 'user_id';
        $temp['FROM'] = 'dw_borrow_tender';
        $temp['WHERE'] = '';
        $temp['and']['dw_user.user_id|='] = 'dw_borrow_tender.user_id/';
        $temp['and']['addtime|>'] = $this->investInfoAddtimeStart($value['invest_addtime_start']);
        $temp['and']['addtime|<'] = $this->investInfoAddTimeEnd($value['invest_addtime_end']);
        $temp['and']['borrow_id|='] = $this->investInfoBorrowId($value['borrow_id']);
        $logic = $this->investInfoIsInvest($value['isinvested']);
        if (!$logic) {
            return [false, false];
        }
        return [$logic, "(" . $this->build($temp) . ")"];
    }

    private function investInfoIsInvest($value)
    {
        switch ($value) {
            case '0':
                return 'NOT EXISTS';
                break;
            case '1':
                return 'EXISTS';
                break;
            default:
                break;
        }
        return false;
    }

    private function investInfoBorrowId($value)
    {
        if ($value === 'false') {
            return false;
        }
        return $value;
    }

    private function investInfoAddtimeStart($value)
    {
        if ($value === 'false') {
            return false;
        }
        return $value;
    }

    private function investInfoAddTimeEnd($value)
    {
        if ($value === 'false') {
            return false;
        }
        return $value;
    }

    private function channelInfoWayType($value)
    {
        if ($value === 'false' || $value === null) {
            return false;
        }
        if(strpos($value, ',') !== false) {
            return '(' . urldecode($value) . ')';
        }
        return $value;
    }

    private function channelInfoUpsId($value)
    {
        if ($value === 'false' || $value === null) {
            return false;
        }
        return $value;
    }

    private function channelInfoId($value)
    {
        if ($value === 'false' || $value === null) {
            return false;
        }
        return $value;
    }

    private function postSubject($value)
    {
        if ($value === 'false') {
            return false;
        }
        return '%' . urldecode($value) . '%';
    }

    private function postTimeStart($value)
    {
        if ($value === 'false') {
            return false;
        }
        return $value;
    }

    private function postTimeEnd($value)
    {
        if ($value === 'false') {
            return false;
        }
        return $value;
    }

    private function postTextSize($value)
    {
        if ($value === 'false') {
            return [false, false];
        }
        return [
            '(SELECT CHAR_LENGTH(message) FROM itz_forum_post WHERE itz_forum_thread.tid = itz_forum_post.tid AND FIRST = 1)|>=',
            (int)$value
        ];
    }

    private function postUserDistinct($value)
    {
        switch ($value) {
            case '1':
                return ['SELECT', 'DISTINCT itz_forum_thread.authorid'];
                break;
            case '2':
                return ['SELECT', 'itz_forum_thread.authorid'];
                break;
            default:
                break;
        }
        return [false, false];
    }

    private function postReplies($condition)
    {
        switch ($condition['post_replies']) {
            case '1':
                return [false, false];
                break;
            case '2':
                return $this->postBonusInfoDistinctRepliesSubQuery($condition['post_replies_num']);
                break;
            case '3':
                return [
                    'itz_forum_thread.replies|>=',
                    (int)$condition['post_replies_num']
                ];
                break;
            default:
                break;
        }
        return [false, false];
    }

    private function postBonusInfoMessageSubQuery($value)
    {
        if ($value === 'false') {
            return [false, false];
        }
        return [
            '(SELECT CHAR_LENGTH(message) FROM itz_forum_post WHERE itz_forum_thread.tid = itz_forum_post.tid AND FIRST = 1)|>=',
            (int)$value
        ];
    }

    private function postBonusInfoDistinctRepliesSubQuery($value)
    {
        return [
            '(SELECT count(DISTINCT authorid) FROM itz_forum_post WHERE itz_forum_thread.tid = itz_forum_post.tid)|>=',
            (int)$value
        ];
    }

    private function repliesInfoPostId($value)
    {
        if ($value === 'false') {
            return false;
        }
        return $value . '/';
    }

    private function repliesInfoTimeStart($value)
    {
        if ($value === 'false') {
            return false;
        }
        return $value;
    }

    private function repliesInfoTimeEnd($value)
    {
        if ($value === 'false') {
            return false;
        }
        return $value;
    }

    private function repliesInfoFloor($value)
    {
        if (strpos($value, '*') !== false) {
            $value = str_replace('*', '%', $value);
            return ['itz_forum_post.position| LIKE', $value];
        }
        if ($value === 'false') {
            return [false, false];
        }
        return ['itz_forum_post.position|>=', addslashes($value)];
    }

    private function repliesInfoEndFloor($value)
    {
        if ($value === 'false') {
            return false;
        }
        return $value . '/';
    }

    private function repliesInfoKeywords($value)
    {
        if ($value === 'false') {
            return false;
        }
        $value = urldecode($value);
        return "%{$value}%";
    }

    private function repliesInfoUserDistinct($value)
    {
        switch ($value) {
            case '1':
                return ['SELECT', 'DISTINCT authorid'];
                break;
            case '2':
                return ['SELECT', 'authorid'];
                break;
            default:
                break;
        }
        return ['SELECT', 'authorid'];
    }

    private function voteBonusInfoTid($value)
    {
        if ($value === 'false') {
            return false;
        }
        return $value . '/';
    }

    private function voteBonusInfoOptions($value)
    {
        if ($value === 'false') {
            return false;
        }
        return $value . '/';
    }

    private function voteBonusInfoDateLineStart($value)
    {
        if ($value === 'false') {
            return false;
        }
        return $value;
    }

    private function voteBonusInfoDateLineEnd($value)
    {
        if ($value === 'false') {
            return false;
        }
        return $value;
    }

    private function voteBonusInfoUserDistinct($value)
    {
        switch ($value) {
            case '1':
                return ['SELECT', 'DISTINCT uid'];
                break;
            case '2':
                return ['SELECT', 'uid'];
                break;
            default:
                break;
        }
        return ['SELECT', 'uid'];
    }

    /**
     * @param $condition
     */
    private function SQLA($condition)
    {
        $this->addCondition('dw_user.phone_status|=', $this->userInfoPhoneStatus($condition['phone_status']), 'and');
        $this->addCondition('dw_user.email_status|=', $this->userInfoEmailStatus($condition['email_status']), 'and');
        $this->addCondition('dw_user.real_status|=', $this->userInfoRealStatus($condition['real_status']), 'and');
        $this->addCondition('dw_user.reg_device|=', $this->userInfoRegDevice($condition['reg_device']), 'and');
        $this->addCondition('dw_user.addtime|>', $this->userInfoAddtimeStart($condition['addtime_start']), 'and');
        $this->addCondition('dw_user.addtime |<', $this->userInfoAddtimeEnd($condition['addtime_end']), 'and');
        $this->addCondition('dw_user.status|=', $this->userInfoStatus($condition['status']), 'and');
        $this->addCondition('dw_user.type_id|=', 2, 'and');
    }

    /**
     * @param $condition
     */
    private function SQLB($condition)
    {
        $condition['channel_waytype'] = urldecode($condition['channel_waytype']);
        if(strpos($condition['channel_waytype'], ',') !== false) {
            $this->addCondition('itz_channel_log.waytype| in', $this->channelInfoWayType($condition['channel_waytype']) . '/', 'and');
        } else {
            $this->addCondition('itz_channel_log.waytype|=', $this->channelInfoWayType($condition['channel_waytype']), 'and');
        }
        $this->addCondition('itz_channel_log.channel_ups_id|=', $this->channelInfoUpsId($condition['channel_ups_id']), 'and');
        $this->addCondition('itz_channel_log.channel_id|=', $this->channelInfoId($condition['channel_id']), 'and');
        $this->addCondition('itz_channel_log.type|=', '1', 'and');
        $this->addCondition('itz_channel_log.is_pay|=', '1', 'and');
    }

    /**
     * @param $condition
     * @return array
     */
    private function SQLC($condition)
    {
        $sql = $this->rechargeInfoSubquery($condition);
        $this->addCondition(current($sql), end($sql) . '/', 'and');
        return $sql;
    }

    /**
     * @param $condition
     */
    private function SQLD($condition)
    {
        $sql = $this->investInfoSubQuery($condition);
        $this->addCondition(current($sql) . '| ', end($sql) . '/', 'and');
    }

    /**
     * [trial 对外统一试算接口]
     * @param  [type] $condition [条件]
     * @return [type]            [试算值]
     */
    public function trial($condition, $skip = false, $limit = false)
    {
        return current(current($this->uniformQuery($condition, self::TRIAL_QUERY, $skip, $limit)));
    }

    public function full($condition, $skip = false, $limit = false)
    {
        return $this->uniformQuery($condition, self::FULL_QUERY, $skip, $limit);
    }

    /**
     * [uniformQuery 内部查询接口]
     * @param  [type]  $condition [条件]
     * @param  boolean $type [查询类型]
     * @return [type]             [查询结果]
     */
    private function uniformQuery($condition, $type = false, $skip = false, $limit = false)
    {
        $this->clear();
        if (isset($condition['award_type'])) {
            return $this->query($this->buildBBSSQL($condition, $type)->skip($skip, $limit)->build());
        } else {
            return $this->query($this->buildMasterSQL($condition, $type)->skip($skip, $limit)->build(), true);
        }
    }

    private function query($sql, $type = false)
    {
        Yii::log(print_r($sql, true), 'info', 'UserGroupSQLService');
        if($type) {
            return Yii::app()->dwdb->createCommand($sql)->queryAll();
        } else {
            return Yii::app()->rbbs->createCommand($sql)->queryAll();
        }
    }
    public function clear() {
        $this->sql = [];
        return $this;
    }
    public function skip($skip, $limit) {
        if($skip !== false && $limit !== false) {
            $this->addCondition('LIMIT', implode(',', [(int) $skip, (int) $limit]));
        }
        return $this;
    }
    public function getSql() {
        return $this->sql;
    }

    /**
     * @param $condition
     * @return mixed
     */
    private function hack($condition)
    {
        if (isset($_POST['condition'])) {
            if (isset($_POST['condition']['channelFun']) && $_POST['condition']['channelFun'] !== "false") {
                $condition['channel_waytype'] = $_POST['condition']['channelFun'];
                return $condition;
            }
            return $condition;
        } else {
            if (isset($condition['channelFun']) && $condition['channelFun'] !== "false") {
                $condition['channel_waytype'] = $condition['channelFun'];
                return $condition;
            }
            return $condition;
        }
    }
}