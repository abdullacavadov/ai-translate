const $=id=>document.getElementById(id);
const source=$("sourceText"), result=$("resultText"), from=$("fromLanguage"), to=$("toLanguage");
const status=$("statusText");

let debounceTimer=null;
let activeController=null;
let requestId=0;
let lastRequestKey="";
const translationCache=new Map();

function updateCharCount(){
  $("charCount").textContent=source.value.length+" / 5000";
}

function getRequestKey(){
  return JSON.stringify({
    text:source.value.trim(),
    from:from.value,
    to:to.value
  });
}

function scheduleTranslate(delay=400){
  clearTimeout(debounceTimer);

  const text=source.value.trim();
  if(!text){
    if(activeController)activeController.abort();
    result.textContent="Translation will appear here...";
    status.textContent="Ready";
    lastRequestKey="";
    return;
  }

  status.textContent="Typing...";
  debounceTimer=setTimeout(()=>translate(),delay);
}

source.addEventListener("input",()=>{
  updateCharCount();
  scheduleTranslate();
});

from.addEventListener("change",()=>scheduleTranslate(100));
to.addEventListener("change",()=>scheduleTranslate(100));

$("clearBtn").addEventListener("click",()=>{
  if(activeController)activeController.abort();
  clearTimeout(debounceTimer);
  source.value="";
  result.textContent="Translation will appear here...";
  updateCharCount();
  status.textContent="Ready";
  lastRequestKey="";
});

$("copyBtn").addEventListener("click",async()=>{
  const text=result.textContent;
  if(!text||text==="Translation will appear here...")return;

  try{
    await navigator.clipboard.writeText(text);
    status.textContent="Copied";
  }catch{
    status.textContent="Copy failed";
  }
});

$("swapBtn").addEventListener("click",()=>{
  if(from.value==="auto")return;

  [from.value,to.value]=[to.value,from.value];
  [source.value,result.textContent]=[result.textContent,source.value];
  updateCharCount();
  lastRequestKey="";
  scheduleTranslate(100);
});

async function translate(){
  clearTimeout(debounceTimer);

  const text=source.value.trim();
  if(!text)return;

  const key=getRequestKey();
  if(key===lastRequestKey)return;

  if(translationCache.has(key)){
    result.textContent=translationCache.get(key);
    lastRequestKey=key;
    status.textContent="Translated";
    return;
  }

  if(activeController)activeController.abort();

  const controller=new AbortController();
  activeController=controller;
  const currentRequest=++requestId;

  result.textContent="";
  status.textContent="Translating...";

  try{
    const response=await fetch("api/translate.php",{
      method:"POST",
      headers:{"Content-Type":"application/json"},
      body:JSON.stringify({
        text,
        from:from.value,
        to:to.value
      }),
      signal:controller.signal
    });

    let data=null;

    try{
      data=await response.json();
    }catch{
      throw new Error("Invalid translation service response.");
    }

    if(!response.ok||!data.success){
      throw new Error(data.message||"Translation failed.");
    }

    if(controller.signal.aborted||currentRequest!==requestId)return;

    const translation=data.translation||"";
    if(!translation)throw new Error("Google Translation returned an empty result.");

    result.textContent=translation;
    translationCache.set(key,translation);
    lastRequestKey=key;
    status.textContent="Translated";
  }catch(error){
    if(error.name==="AbortError")return;
    if(currentRequest!==requestId)return;
    result.textContent="";
    status.textContent=error.message;
  }finally{
    if(activeController===controller)activeController=null;
  }
}

function speak(text,lang){
  if(!text||!("speechSynthesis" in window))return;

  window.speechSynthesis.cancel();

  const u=new SpeechSynthesisUtterance(text);
  u.lang=lang==="az"?"az-AZ":lang==="tr"?"tr-TR":lang==="de"?"de-DE":lang==="ru"?"ru-RU":lang==="fr"?"fr-FR":lang==="es"?"es-ES":"en-US";

  speechSynthesis.speak(u);
}

$("speakSourceBtn").addEventListener("click",()=>speak(source.value,from.value==="auto"?"en":from.value));
$("speakResultBtn").addEventListener("click",()=>speak(result.textContent,to.value));

$("themeToggle").addEventListener("click",()=>{
  document.body.classList.toggle("light");
  $("themeToggle").textContent=document.body.classList.contains("light")?"☀":"☾";
});
