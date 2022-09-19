function doverify(apply_id)
{
    if(confirm('确认操作？')){
        location.href = ROOT + '?m=UserFreezeMoney&a=doverify&id='+apply_id+'&type=1';
    }
}

function noverify(apply_id)
{
    if(confirm('确认操作？')){
        location.href = ROOT + '?m=UserFreezeMoney&a=doverify&id='+apply_id+'&type=2';
    }
}
