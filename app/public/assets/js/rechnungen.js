
// NEW POS
document.addEventListener("DOMContentLoaded", function(){
  let newTrigger = document.getElementById("posNeu");
  let posWrap = document.getElementById("posWrap");
  var posCount = posWrap.childElementCount;

  // Add Eventlisteners to all existing Positions
  var existingPos = posWrap.querySelectorAll(".pos");
  for (var i = 0; i < existingPos.length; i++) {
    addPosEventLister(existingPos[i]);
  }

  function newPos(){
    $.ajax({
      url: "https://team.eventverein.de/ajax",
      dataType: "json",
      data: {
        action: "reNewPos",
        nonce: "nonce" // TODO: NOCE
      },
      success: function( data ) {
        var temp = document.createElement("div");
        temp.innerHTML = data.trim();

        var newPos = temp.firstChild;
        newPos.querySelector(".posOrder").value = posCount++;
        newPos.querySelector(".posNr").innerHTML = posCount + ".";
        addPosEventLister(newPos);
        posWrap.appendChild(newPos);
      },
    });
  }

  function addPosEventLister(posObj){
    posObj.querySelector(".posDel").addEventListener("click", delPos);
    posObj.querySelector(".posAnz").addEventListener("change", updatePosSum);
    posObj.querySelector(".posEP").addEventListener("change", updatePosSum);
  }
  function updatePosSum(e){
    let target = e.currentTarget;
    let pos = getPosByEventTarget(target);

    var qty = pos.querySelector(".posAnz").value;
    var ep = pos.querySelector(".posEP").value;

    pos.querySelector(".posSum").value = Math.round( ( ((qty * ep) + Number.EPSILON)) * 100 )  / 100;
  }
  function delPos(e){
    let target = e.currentTarget;
    let pos = getPosByEventTarget(target);
    pos.remove();
    posCount--;
    console.log(posCount);
  }

  function getPosByEventTarget(target, sec = 10){
    var el = target;
    while (!el.classList.contains("pos") && sec > 0) {
      el = el.parentElement;
      sec--;
    }
    return sec == 0 ? false : el;
  }

  newTrigger.addEventListener("click", newPos);
});

// AUTOCOMPLETE STUFF
document.addEventListener("autocompleteLoaded", function(){
  $( "#kontakt" ).catcomplete({
    source: function( request, response ) {
      console.log("getting data...");
      $.ajax({
        url: "https://team.eventverein.de/ajax",
        dataType: "json",
        data: {
          action: "getPartners",
          q: request.term,
          nonce: "nonce" // TODO: NOCE
        },
        success: function( data ) {
          response( data );
        },
      });
    },
    minLength: 1,
    select: function( event, ui ) {
      $("#kontaktId").attr("value", ui.item.id ? ui.item.id : "");
    },
    open: function() {
      $( this ).removeClass( "ui-corner-all" ).addClass( "ui-corner-top" );
    },
    close: function() {
      $( this ).removeClass( "ui-corner-top" ).addClass( "ui-corner-all" );
    }
  });
  $( "#ansprechpartner" ).catcomplete({
    source: function( request, response ) {
      console.log("getting data...");
      $.ajax({
        url: "https://team.eventverein.de/ajax",
        dataType: "json",
        data: {
          action: "getMembers",
          q: request.term,
          nonce: "nonce" // TODO: NOCE
        },
        success: function( data ) {
          response( data );
        },
      });
    },
    minLength: 1,
    select: function( event, ui ) {
      $("#ansprechpartnerId").attr("value", ui.item.id ? ui.item.id : "");
    },
    open: function() {
      $( this ).removeClass( "ui-corner-all" ).addClass( "ui-corner-top" );
    },
    close: function() {
      $( this ).removeClass( "ui-corner-top" ).addClass( "ui-corner-all" );
    }
  });
});
