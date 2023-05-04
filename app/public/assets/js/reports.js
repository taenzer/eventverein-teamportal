
document.addEventListener("DOMContentLoaded", function(e){
  let triggers = document.querySelectorAll(".report .aktionen .generate");
  triggers.forEach((item, i) => {
    item.addEventListener("click", newReport);
  });
});

function newReport(e){
  let target = e.currentTarget;
  let month = target.getAttribute("data-month");
  let year = target.getAttribute("data-year");
  var html = "<div class='reportWarning'><span class='icon outlined'>sms_failed</span><h1>Soll dieser Bericht<br>wirklich erstellt werden?</h1>";
  html += "<p>Du bist gerade dabei den Finanzbericht für " + month + "/" + year + " zu erstellen. Dadurch werden alle Transaktionen abgeschlossen ";
  html += "und können nicht mehr verändert werden. Auch das Erstellen neuer Transaktionen im Berichts-Zeitraum ist danach nicht mehr möglich.</p>";
  html += "<div class='buttons'><a href='#' class='button' onClick='generateTriggeredReport("+month+","+year+")'>Bericht erstellen</a><a href='#' class='button outlined' onCLick='closePopup()'>Abbruch</a></div></div>"
  openPopup(html);
  document.popup.trigger = target;
}

function generateTriggeredReport(controlMonth, controlYear){
  let trigger = document.popup.trigger;
  if(trigger.getAttribute("data-month") != controlMonth || trigger.getAttribute("data-year") != controlYear){
    return false;
  }

  trigger.classList.add("generating");
  trigger.innerHTML = '<span class="icon">settings</span> Wird erstellt...';
  trigger.removeEventListener("click", newReport);
  closePopup();

  console.log("requesting report generation...");
  $.ajax({
    url: "https://team.eventverein.de/ajax",
    dataType: "json",
    data: {
      action: "createReport",
      month: controlMonth,
      year: controlYear,
      nonce: "nonce" // TODO: NOCE
    },
    success: function( data ) {
      if(data == "success"){
        console.log("Report generated!");
        setTimeout(function(){
          location.reload();
        }, 2000);
      }else{
        alert("Es ist ein Fehler aufgetreten. Bitte Konsole prüfen.");
        console.log(data);
      }
    },
    error: function( data ) {
      openPopup("<h1>Fehler beim Erstellen des Berichts:</h1>" +  data.responseText);
      console.log("Fehlerdaten:", data);
    }
  });

}
