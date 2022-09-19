 var triggerScheme = function(scheme) {
     var iframe = document.createElement("iframe");
     iframe.src = scheme;
     iframe.style.display = "none";
     document.body.appendChild(iframe);
 };
 triggerScheme("firstp2p://api?type=rightbtn&title=保存&callback=jsfuncname");