function isMoney(val) {
    if(!isNaN(val)){
        return false;
    }
    return val.length > 0 ? true :false;
}