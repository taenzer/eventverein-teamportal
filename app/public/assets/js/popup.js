document.addEventListener("DOMContentLoaded", function(e){
  initPopups();
  document.popup = document.getElementById("popup");
  document.popupContent = document.popup.querySelector(".popup-content");
});

function initPopups(){
  var popupwrp = document.createElement("div");
  popupwrp.classList.add("popup-wrp");
  popupwrp.id = "popup";

  var popup = document.createElement("div");
  popup.classList.add("popup");
  popup.innerHTML = "<span class='icon' onClick='closePopup()'>close</span><div class='popup-content'></div>";
  popupwrp.appendChild(popup);
  document.getElementById("content").appendChild(popupwrp);
}

function openPopup(html){
  closePopup();
  document.popup.style.display = "block";
  document.popupContent.innerHTML = html;
}

function closePopup(){
  document.popup.style.display = "none";
  document.popupContent.innerHTML = "";
  document.popup.trigger = null;
}
