export const TERRAIN_VS = `#version 300 es
precision highp float;
layout(location=0)in vec3 aPos;
layout(location=1)in vec3 aNormal;
layout(location=2)in vec2 aUv;
layout(location=3)in float aSlopeDeg;
uniform mat4 uViewProj;
out vec3 vWorldPos;
out vec3 vNormal;
out vec2 vUv;
out float vSlopeDeg;
void main(){
vWorldPos=aPos;
vNormal=aNormal;
vUv=aUv;
vSlopeDeg=aSlopeDeg;
gl_Position=uViewProj*vec4(aPos,1.0);
}`;

export const TERRAIN_FS = `#version 300 es
precision highp float;
in vec3 vWorldPos;
in vec3 vNormal;
in vec2 vUv;
in float vSlopeDeg;
uniform sampler2D uMapTex;
uniform vec3 uSunDir;
uniform vec3 uSunColor;
uniform vec3 uSkyColor;
uniform vec3 uGroundColor;
uniform vec3 uFogColor;
uniform vec3 uCameraPos;
uniform float uLightMode;
uniform float uContours;
uniform float uNightLit;
out vec4 fragColor;
void main(){
vec3 texCol=texture(uMapTex,vUv).rgb;
vec3 n=normalize(vNormal);
vec3 sun=normalize(uSunDir);
float ndotl=max(dot(n,sun),0.0);
float hemi=n.y*0.5+0.5;
vec3 lit;
if(uLightMode<0.5){
lit=texCol*mix(0.86,1.05,clamp(ndotl*0.65+hemi*0.35,0.0,1.0));
}else if(uLightMode<1.5){
float elevBlend=smoothstep(4.0,32.0,vWorldPos.y);
vec3 warmTone=mix(vec3(0.96,0.78,0.64),vec3(1.06,0.74,0.52),elevBlend);
lit=texCol*mix(vec3(0.56,0.54,0.68),warmTone,clamp(ndotl*0.75+hemi*0.25,0.0,1.0));
}else{
lit=texCol*mix(vec3(0.24,0.31,0.44),vec3(0.42,0.50,0.66),ndotl*0.5+hemi*0.5);
}
if(uContours>0.5){
float modY=fract(vWorldPos.y/3.5);
float lineMask=1.0-smoothstep(0.0,0.09,min(modY,1.0-modY));
vec3 contourCol=uLightMode>1.5?vec3(0.36,0.54,0.80):vec3(0.18,0.28,0.42);
lit=mix(lit,contourCol,lineMask*0.28);
float t=clamp((vSlopeDeg-7.0)/30.0,0.0,1.0);
vec3 midGrade=mix(vec3(0.20,0.74,0.44),vec3(0.24,0.54,0.94),clamp(t*1.45,0.0,1.0));
vec3 gradeCol=mix(midGrade,vec3(0.90,0.32,0.24),clamp((t-0.45)*1.8,0.0,1.0));
lit=mix(lit,gradeCol*(0.62+0.38*ndotl),0.26);
}
if(uLightMode>1.5&&uNightLit>0.05){
float baseGlow=exp(-pow((vWorldPos.z-285.0)/75.0,2.0))*0.30;
lit+=vec3(0.95,0.66,0.30)*baseGlow*uNightLit;
}
float fog=clamp((length(uCameraPos-vWorldPos)-580.0)/900.0,0.0,0.28);
fragColor=vec4(mix(lit,uFogColor,fog),1.0);
}`;

export const SOLID_VS = `#version 300 es
precision highp float;
layout(location=0)in vec3 aPos;
layout(location=1)in vec3 aNormal;
layout(location=2)in vec4 aColor;
uniform mat4 uViewProj;
out vec3 vWorldPos;
out vec3 vNormal;
out vec4 vColor;
void main(){
vWorldPos=aPos;
vNormal=aNormal;
vColor=aColor;
gl_Position=uViewProj*vec4(aPos,1.0);
}`;

export const SOLID_FS = `#version 300 es
precision highp float;
in vec3 vWorldPos;
in vec3 vNormal;
in vec4 vColor;
uniform vec3 uSunDir;
uniform vec3 uSunColor;
uniform vec3 uSkyColor;
uniform vec3 uGroundColor;
uniform vec3 uFogColor;
uniform vec3 uCameraPos;
uniform float uLightMode;
out vec4 fragColor;
void main(){
vec3 n=normalize(vNormal);
vec3 sun=normalize(uSunDir);
vec3 viewDir=normalize(uCameraPos-vWorldPos);
float ndotl=max(dot(n,sun),0.0);
vec3 ambient=mix(uGroundColor,uSkyColor,n.y*0.5+0.5);
float spec=pow(max(dot(n,normalize(sun+viewDir)),0.0),28.0)*0.14;
float rim=pow(1.0-max(dot(n,viewDir),0.0),3.0)*0.08;
float emissiveBoost=uLightMode>1.5?max(0.28,vColor.a*1.25):vColor.a*0.45;
vec3 base=vColor.rgb*(ambient*0.55+uSunColor*(ndotl*0.65+0.35))+vColor.rgb*emissiveBoost+uSunColor*(spec+rim);
float fog=clamp((length(uCameraPos-vWorldPos)-580.0)/900.0,0.0,0.25);
fragColor=vec4(mix(base,uFogColor,fog),1.0);
}`;

export const INSTANCED_VS = `#version 300 es
precision highp float;
layout(location=0)in vec3 aPos;
layout(location=1)in vec3 aNormal;
layout(location=2)in vec3 aBaseColor;
layout(location=3)in vec3 aInstPos;
layout(location=4)in vec2 aInstYawScale;
layout(location=5)in vec4 aInstColor;
uniform mat4 uViewProj;
out vec3 vNormal;
out vec3 vColor;
out vec3 vWorldPos;
void main(){
float c=cos(aInstYawScale.x);
float s=sin(aInstYawScale.x);
vec3 p=aPos*aInstYawScale.y;
vec3 rotPos=vec3(p.x*c+p.z*s,p.y,-p.x*s+p.z*c)+aInstPos;
vWorldPos=rotPos;
vNormal=vec3(aNormal.x*c+aNormal.z*s,aNormal.y,-aNormal.x*s+aNormal.z*c);
vColor=mix(aBaseColor,aInstColor.rgb,step(0.0,-aBaseColor.r)*aInstColor.a);
gl_Position=uViewProj*vec4(rotPos,1.0);
}`;

export const INSTANCED_FS = `#version 300 es
precision highp float;
in vec3 vNormal;
in vec3 vColor;
in vec3 vWorldPos;
uniform vec3 uSunDir;
uniform vec3 uSunColor;
uniform vec3 uSkyColor;
uniform vec3 uGroundColor;
uniform float uLightMode;
out vec4 fragColor;
void main(){
vec3 n=normalize(vNormal);
float ndotl=max(dot(n,normalize(uSunDir)),0.0);
vec3 ambient=mix(uGroundColor,uSkyColor,n.y*0.5+0.5);
float nightPop=uLightMode>1.5?0.22:0.06;
fragColor=vec4(vColor*(ambient*0.58+uSunColor*(ndotl*0.65+0.35)+nightPop),1.0);
}`;

export const RIBBON_VS = `#version 300 es
precision highp float;
layout(location=0)in vec3 aPos;
layout(location=1)in vec3 aSide;
layout(location=2)in vec4 aColorWidth;
layout(location=3)in vec4 aUvStyle;
layout(location=4)in vec2 aMeta;
uniform mat4 uViewProj;
uniform vec3 uCameraPos;
uniform float uHoveredSegIdx;
uniform float uSelectedSegIdx;
out vec3 vWorldPos;
out vec3 vColor;
out vec2 vCap;
out float vAlong;
out float vStyle;
out float vNightLit;
out float vHovered;
out float vSelected;
out float vHalfW;
void main(){
float isSel=step(abs(aMeta.x-uSelectedSegIdx),0.25);
float isHov=step(abs(aMeta.x-uHoveredSegIdx),0.25)*(1.0-isSel);
float distScale=clamp(pow(max(1.0,length(uCameraPos-aPos))/165.0,0.40),0.74,1.80);
float stateBoost=mix(1.0+isHov*0.22+isSel*0.42,1.0,step(5.5,aUvStyle.w));
float isUnbuilt=step(abs(aUvStyle.w-1.0),0.25)+step(abs(aUvStyle.w-4.0),0.25);
float farT=smoothstep(140.0,420.0,length(uCameraPos-aPos));
float farBoost=mix(1.0,1.7,isUnbuilt*farT);
float halfW=aColorWidth.a*distScale*stateBoost*farBoost;
// Unbuilt runs sit lower than built ones; far away the depth buffer can't tell them from the snow, so lift them
float yExtra=max(0.0,distScale-1.0)*0.045+isSel*0.025+isHov*0.012+isUnbuilt*farT*0.35;
vec3 worldPos=aPos+aSide*halfW+vec3(0.0,yExtra,0.0);
vWorldPos=worldPos;
vColor=aColorWidth.rgb;
vCap=aUvStyle.xy;
vAlong=aUvStyle.z;
vStyle=aUvStyle.w;
vNightLit=aMeta.y;
vHovered=isHov;
vSelected=isSel;
vHalfW=halfW;
gl_Position=uViewProj*vec4(worldPos,1.0);
}`;

export const RIBBON_FS = `#version 300 es
precision highp float;
in vec3 vWorldPos;
in vec3 vColor;
in vec2 vCap;
in float vAlong;
in float vStyle;
in float vNightLit;
in float vHovered;
in float vSelected;
in float vHalfW;
uniform vec3 uFogColor;
uniform vec3 uCameraPos;
uniform float uLightMode;
uniform float uTime;
out vec4 fragColor;
float computePillEdge(float along,float halfW,vec2 cap,float period,float flatHalf,float capXAa,float alongAa,out float pillAa){
float dAlong=abs(fract(along/period)-0.5)*period;
float capScale=max(0.22,halfW*0.92);
float pillAlong=max(0.0,dAlong-flatHalf)/capScale;
pillAa=max(0.04,capXAa*1.25+(alongAa/capScale)*0.55);
return length(vec2(cap.x,max(abs(cap.y),pillAlong)));
}
void main(){
int st=int(vStyle+0.5);
float radialEdge=length(vCap);
float radialAa=max(0.035,fwidth(radialEdge)*1.25);
float capXAa=fwidth(vCap.x);
float alongAa=fwidth(vAlong);
float diagPhase=(vAlong+vCap.x*1.1)/3.2;
float diagAa=max(0.04,fwidth(diagPhase)*1.25);
float chevPhase=(vAlong-uTime*3.8+abs(vCap.x)*1.55)/4.2;
float chevAa=max(0.04,fwidth(chevPhase)*1.3);
vec3 casingCol=uLightMode<0.5?vec3(0.96,0.98,1.0):(uLightMode<1.5?vec3(0.98,0.94,0.90):vec3(0.80,0.88,0.96));
bool isNightGlow=(vNightLit>0.5&&uLightMode>0.5);
vec3 shadowCol=vec3(0.06,0.10,0.16);
vec3 outerBaseCol=isNightGlow?vec3(1.0,0.84,0.42):shadowCol;
float edge=radialEdge;
float aa=radialAa;
float coreR=0.56;
float caseR=0.78;
vec3 coreCol=vColor;
vec3 outerCol=outerBaseCol;
float outerMax=isNightGlow?0.52:0.34;
float opacity=1.0;
if(st==6){
coreR=0.52;
caseR=0.80;
outerCol=shadowCol;
outerMax=0.45;
}else if(st==1&&vSelected<0.5){
float pillAa;
float pillEdge=computePillEdge(vAlong,vHalfW,vCap,3.6,0.32,capXAa,alongAa,pillAa);
// Far away the dots get smaller than a pixel and vanish: blend them into a plain thin line
float farDots=smoothstep(0.35,0.8,alongAa);
edge=mix(pillEdge,radialEdge,farDots);
aa=mix(pillAa,radialAa,farDots);
// As a far line, most of the width is the run's colour so difficulty still reads
coreR=mix(0.62,0.82,farDots);
caseR=mix(0.86,0.94,farDots);
outerCol=shadowCol;
outerMax=0.35;
opacity=mix(mix(0.74,0.92,farDots),1.0,vHovered);
}else if((st==3||st==4)&&vSelected<0.5){
float period=st==3?4.6:4.0;
float flatHalf=st==3?1.25:0.95;
float pillAa;
float pillEdge=computePillEdge(vAlong,vHalfW,vCap,period,flatHalf,capXAa,alongAa,pillAa);
float farDash=smoothstep(0.45,1.0,alongAa);
edge=mix(pillEdge,radialEdge,farDash);
aa=mix(pillAa,radialAa,farDash);
coreR=0.60;
caseR=0.84;
outerCol=shadowCol;
outerMax=0.32;
opacity=st==3?mix(0.60,0.95,vHovered):mix(0.76,1.0,vHovered);
}else{
float centerSheen=(1.0-smoothstep(0.0,0.52,abs(vCap.x)))*0.13;
coreCol=clamp(vColor+vec3(centerSheen),0.0,1.0);
if(st==5){
float trackGroove=1.0-smoothstep(0.04,0.11,abs(abs(vCap.x)-0.18));
coreCol=mix(coreCol,vec3(0.90,0.97,0.96),trackGroove*0.32);
}else if(st==2){
float luma=dot(vColor,vec3(0.30,0.59,0.11));
vec3 muted=mix(vColor,vec3(luma),0.52);
float diagDist=abs(fract(diagPhase)-0.5);
float barMask=1.0-smoothstep(0.18-diagAa,0.18+diagAa,diagDist);
coreCol=mix(muted,vec3(0.18,0.22,0.28),barMask*0.72);
opacity=mix(0.88,1.0,max(vHovered,vSelected));
}
if(vHovered>0.5||vSelected>0.5){
float chevDist=abs(fract(chevPhase)-0.5);
float chevMask=1.0-smoothstep(0.09-chevAa,0.09+chevAa,chevDist);
coreCol=mix(coreCol,vec3(0.98,0.99,1.0),chevMask*(vSelected>0.5?0.26:0.16));
}
if(vSelected>0.5){
coreR=0.44;
caseR=0.66;
outerCol=vec3(0.86,0.97,1.0);
outerMax=0.78;
}
}
if(edge>1.0+aa){discard;}
float caseMix=smoothstep(coreR-aa,coreR+aa,edge);
vec3 innerCol=mix(coreCol,casingCol,caseMix);
float rimMix=smoothstep(caseR-aa,caseR+aa,edge);
vec3 rgb=mix(innerCol,outerCol,rimMix);
float outerFalloff=pow(clamp(1.0-(edge-caseR)/max(0.02,1.0-caseR),0.0,1.0),1.45);
float alpha=mix(1.0,outerMax*outerFalloff,rimMix)*opacity;
bool isUnbuilt=(st==1||st==4)&&vSelected<0.5;
if(uLightMode>0.5&&uLightMode<1.5){
rgb*=vec3(1.02,0.96,0.90);
}else if(uLightMode>=1.5){
if(isUnbuilt){
rgb*=0.68;
alpha*=0.84;
}else{
rgb*=mix(0.82,1.04,clamp(vNightLit+vSelected*0.8+vHovered*0.5,0.0,1.0));
}
}
float fog=clamp((length(uCameraPos-vWorldPos)-580.0)/900.0,0.0,0.25);
rgb=mix(rgb,uFogColor,fog);
if(alpha<0.004){discard;}
fragColor=vec4(rgb,alpha);
}`;

export function compileProgram(gl: WebGL2RenderingContext, vsSrc: string, fsSrc: string): WebGLProgram {
  const compile = (type: number, src: string) => {
    const sh = gl.createShader(type);
    if (!sh) throw new Error('Could not create shader');
    gl.shaderSource(sh, src);
    gl.compileShader(sh);
    if (!gl.getShaderParameter(sh, gl.COMPILE_STATUS)) {
      const log = gl.getShaderInfoLog(sh) || 'Shader compile failed';
      gl.deleteShader(sh);
      throw new Error(log);
    }
    return sh;
  };
  const vs = compile(gl.VERTEX_SHADER, vsSrc);
  const fs = compile(gl.FRAGMENT_SHADER, fsSrc);
  const prog = gl.createProgram();
  if (!prog) throw new Error('Could not create WebGL program');
  gl.attachShader(prog, vs);
  gl.attachShader(prog, fs);
  gl.linkProgram(prog);
  gl.deleteShader(vs);
  gl.deleteShader(fs);
  if (!gl.getProgramParameter(prog, gl.LINK_STATUS)) {
    const log = gl.getProgramInfoLog(prog) || 'Program link failed';
    gl.deleteProgram(prog);
    throw new Error(log);
  }
  return prog;
}
