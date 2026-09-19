const $=id=>document.getElementById(id);
const source=$("sourceText"), result=$("resultText"), from=$("fromLanguage"), to=$("toLanguage");

source.addEventListener("input",()=>{$("charCount").textContent=source.value.length+" / 5000"});
$("clearBtn").addEventListener("click",()=>{source.value="";result.textContent="Translation will appear here...";$("charCount").textContent="0 / 5000";$("statusText").textContent="Ready"});
$("copyBtn").addEventListener("click",async()=>{const text=result.textContent;if(!text||text==="Translation will appear here...")return;await navigator.clipboard.writeText(text);$("statusText").textContent="Copied"});
$("swapBtn").addEventListener("click",()=>{if(from.value==="auto")return;[from.value,to.value]=[to.value,from.value];[source.value,result.textContent]=[result.textContent,source.value];$("charCount").textContent=source.value.length+" / 5000"});
$("translateBtn").addEventListener("click",translate);

async function translate(){
 const text=source.value.trim(); if(!text)return;
 const btn=$("translateBtn"), spinner=$("spinner");
 btn.disabled=true;spinner.classList.remove("d-none");$("buttonText").textContent="Translating...";$("statusText").textContent="AI is translating...";
 try{
   const response=await fetch("api/translate.php",{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({text,from:from.value,to:to.value,tone:$("tone").value})});
   const data=await response.json();
   if(!response.ok||!data.success)throw new Error(data.message||"Translation failed.");
   result.textContent=data.translation;$("statusText").textContent="Translated";
 }catch(error){result.textContent="";$("statusText").textContent=error.message}
 finally{btn.disabled=false;spinner.classList.add("d-none");$("buttonText").textContent="Translate with AI"}
}
function speak(text,lang){if(!text||!("speechSynthesis" in window))return;window.speechSynthesis.cancel();const u=new SpeechSynthesisUtterance(text);u.lang=lang==="az"?"az-AZ":lang==="tr"?"tr-TR":lang==="de"?"de-DE":lang==="ru"?"ru-RU":lang==="fr"?"fr-FR":lang==="es"?"es-ES":"en-US";speechSynthesis.speak(u)}
$("speakSourceBtn").addEventListener("click",()=>speak(source.value,from.value==="auto"?"en":from.value));
$("speakResultBtn").addEventListener("click",()=>speak(result.textContent,to.value));
$("themeToggle").addEventListener("click",()=>{document.body.classList.toggle("light");$("themeToggle").textContent=document.body.classList.contains("light")?"☀":"☾"});
