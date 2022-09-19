function doverify(apply_id)
{
	if(confirm('确认操作？')){
		location.href = ROOT + '?m=MoneyApply&a=doverify&id='+apply_id+'&type=2';
	}
}

function noverify(apply_id)
{
	if(confirm('确认操作？')){
		location.href = ROOT + '?m=MoneyApply&a=doverify&id='+apply_id+'&type=3';
	}
}