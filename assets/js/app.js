const $=id=>document.getElementById(id);
const source=$("sourceText"), result=$("resultText"), from=$("fromLanguage"), to=$("toLanguage");
const status=$("statusText");

let debounceTimer=null;
let activeController=null;
let requestId=0;
let lastRequestKey="";

function updateCharCount(){
  $("charCount").textContent=source.value.length+" / 5000";
}

function getRequestKey(){
  return JSON.stringify({
    text:source.value.trim(),
    from:from.value,
    to:to.value,
    tone:$("tone").value
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

from.addEventListener("change",()=>scheduleTranslate(150));
to.addEventListener("change",()=>scheduleTranslate(150));
$("tone").addEventListener("change",()=>scheduleTranslate(150));

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
  scheduleTranslate(150);
});

async function translate(){
  clearTimeout(debounceTimer);

  const text=source.value.trim();
  if(!text)return;

  const key=getRequestKey();
  if(key===lastRequestKey)return;

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
        to:to.value,
        tone:$("tone").value
      }),
      signal:controller.signal
    });

    if(!response.ok){
      let message="Translation failed.";
      try{
        const data=await response.json();
        message=data.message||message;
      }catch{}
      throw new Error(message);
    }

    if(!response.body)throw new Error("Streaming is not supported by this browser.");

    const reader=response.body.getReader();
    const decoder=new TextDecoder();
    let buffer="";
    let translation="";

    while(true){
      const {value,done}=await reader.read();
      if(done)break;

      buffer+=decoder.decode(value,{stream:true});

      while(true){
        const separator=buffer.indexOf("\n\n");
        if(separator===-1)break;

        const block=buffer.slice(0,separator);
        buffer=buffer.slice(separator+2);

        const line=block.split(/\r?\n/).find(line=>line.startsWith("data:"));
        if(!line)continue;

        const payload=line.slice(5).trim();
        if(!payload)continue;

        const event=JSON.parse(payload);

        if(event.type==="text"){
          translation+=event.text||"";
          result.textContent=translation;
        }

        if(event.type==="error"){
          throw new Error(event.message||"Translation failed.");
        }

        if(event.type==="done"){
          lastRequestKey=key;
          status.textContent="Translated";
        }
      }
    }

    if(controller.signal.aborted||currentRequest!==requestId)return;

    if(!translation){
      throw new Error("AI returned an empty translation.");
    }

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
