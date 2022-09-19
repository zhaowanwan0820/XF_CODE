<?php
namespace core\dao;

use libs\utils\XDateTime;

class DealTransferLogModel extends BaseModel
{
    public static function create(BaseModel $owner, $fromUserId, $toUserId, $money)
    {
        $self = new self();
        $self->ownerid = $owner->id;
        $self->ownertype = getClassName4Db($owner);
        $self->fromuserid = $fromUserId;
        $self->touserid  = $toUserId;
        $self->money = $money;
        $self->create_time = XDateTime::now()->toString();
        return $self;
    }

    public function findByOwner(BaseModel $owner)
    {
        $sql = "SELECT\n".
            "   *\n".
            "FROM\n".
            "   `firstp2p_deal_transfer_log`\n".
            "WHERE\n".
            "   ownerid = ':ownerid'\n".
            "AND ownertype = ':ownertype'";
        $binds = array(
            ':ownerid' => $owner->id,
            ':ownertype' => getClassName4Db($owner),
            );
        return $this->findBySql($sql, $binds);
    }
}
