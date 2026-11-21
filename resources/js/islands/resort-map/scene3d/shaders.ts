export const TERRAIN_VS = `#version 300 es
precision highp float;
layout(location=0) in vec3 aPos;
layout(location=1) in vec3 aNormal;
layout(location=2) in vec2 aUv;
layout(location=3) in float aSlopeDeg;

uniform mat4 uViewProj;
out vec3 vWorldPos;
out vec3 vNormal;
out vec2 vUv;
out float vSlopeDeg;

void main() {
  vWorldPos = aPos;
  vNormal = aNormal;
  vUv = aUv;
  vSlopeDeg = aSlopeDeg;
  gl_Position = uViewProj * vec4(aPos, 1.0);
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
uniform float uLightMode; // 0=day, 1=sunset, 2=night
uniform float uContours;  // 0=off, 1=steepness lens contours
uniform float uNightLit;  // >0.05 warms lower village & base lifts at night

out vec4 fragColor;

void main() {
  vec3 texCol = texture(uMapTex, vUv).rgb;
  vec3 n = normalize(vNormal);
  vec3 sun = normalize(uSunDir);
  float ndotl = max(dot(n, sun), 0.0);
  float hemi = n.y * 0.5 + 0.5;

  vec3 lit;
  if (uLightMode < 0.5) {
    // Day: preserve pre-shaded trail artwork clarity with crisp gentle directional modulation
    float relief = mix(0.86, 1.05, clamp(ndotl * 0.65 + hemi * 0.35, 0.0, 1.0));
    lit = texCol * relief;
  } else if (uLightMode < 1.5) {
    // Sunset: warm alpenglow on upper sun-facing terrain + cool alpine shadow fill
    float elevBlend = smoothstep(4.0, 32.0, vWorldPos.y);
    vec3 warmTone = mix(vec3(0.96, 0.78, 0.64), vec3(1.06, 0.74, 0.52), elevBlend);
    vec3 coolShadow = vec3(0.56, 0.54, 0.68);
    vec3 tint = mix(coolShadow, warmTone, clamp(ndotl * 0.75 + hemi * 0.25, 0.0, 1.0));
    lit = texCol * tint;
  } else {
    // Night: crisp moonlit blue-slate keeping trail lines legible
    vec3 nightTint = mix(vec3(0.24, 0.31, 0.44), vec3(0.42, 0.50, 0.66), ndotl * 0.5 + hemi * 0.5);
    lit = texCol * nightTint;
  }

  // Topographic elevation contour lines & grade wash rendered strictly on Steepness lens
  if (uContours > 0.5) {
    float stepY = 3.5;
    float modY = fract(vWorldPos.y / stepY);
    float lineMask = 1.0 - smoothstep(0.0, 0.09, min(modY, 1.0 - modY));
    vec3 contourCol = uLightMode > 1.5 ? vec3(0.36, 0.54, 0.80) : vec3(0.18, 0.28, 0.42);
    lit = mix(lit, contourCol, lineMask * 0.28);

    float t = clamp((vSlopeDeg - 7.0) / 30.0, 0.0, 1.0);
    vec3 midGrade = mix(vec3(0.20, 0.74, 0.44), vec3(0.24, 0.54, 0.94), clamp(t * 1.45, 0.0, 1.0));
    vec3 gradeCol = mix(midGrade, vec3(0.90, 0.32, 0.24), clamp((t - 0.45) * 1.8, 0.0, 1.0));
    lit = mix(lit, gradeCol * (0.62 + 0.38 * ndotl), 0.26);
  }

  // Warm village & base night-skiing glow in evening/night lighting
  if (uLightMode > 1.5 && uNightLit > 0.05) {
    float baseGlow = exp(-pow((vWorldPos.z - 285.0) / 75.0, 2.0)) * 0.30;
    lit += vec3(0.95, 0.66, 0.30) * baseGlow * uNightLit;
  }

  float dist = length(uCameraPos - vWorldPos);
  float fog = clamp((dist - 580.0) / 900.0, 0.0, 0.28);
  lit = mix(lit, uFogColor, fog);
  fragColor = vec4(lit, 1.0);
}`;

export const SOLID_VS = `#version 300 es
precision highp float;
layout(location=0) in vec3 aPos;
layout(location=1) in vec3 aNormal;
layout(location=2) in vec4 aColor; // rgb + a=emissive strength

uniform mat4 uViewProj;
out vec3 vWorldPos;
out vec3 vNormal;
out vec4 vColor;

void main() {
  vWorldPos = aPos;
  vNormal = aNormal;
  vColor = aColor;
  gl_Position = uViewProj * vec4(aPos, 1.0);
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

void main() {
  vec3 n = normalize(vNormal);
  vec3 sun = normalize(uSunDir);
  vec3 viewDir = normalize(uCameraPos - vWorldPos);
  float ndotl = max(dot(n, sun), 0.0);
  float hemi = n.y * 0.5 + 0.5;
  vec3 ambient = mix(uGroundColor, uSkyColor, hemi);

  vec3 halfVec = normalize(sun + viewDir);
  float spec = pow(max(dot(n, halfVec), 0.0), 28.0) * 0.14;
  float rim = pow(1.0 - max(dot(n, viewDir), 0.0), 3.0) * 0.08;

  float emissiveBoost = uLightMode > 1.5 ? max(0.28, vColor.a * 1.25) : vColor.a * 0.45;
  vec3 base = vColor.rgb * (ambient * 0.55 + uSunColor * (ndotl * 0.65 + 0.35))
    + vColor.rgb * emissiveBoost
    + uSunColor * (spec + rim);

  float dist = length(uCameraPos - vWorldPos);
  float fog = clamp((dist - 580.0) / 900.0, 0.0, 0.25);
  fragColor = vec4(mix(base, uFogColor, fog), 1.0);
}`;

export const INSTANCED_VS = `#version 300 es
precision highp float;
layout(location=0) in vec3 aPos;
layout(location=1) in vec3 aNormal;
layout(location=2) in vec3 aBaseColor;
layout(location=3) in vec3 aInstPos;
layout(location=4) in vec2 aInstYawScale; // x = yaw radians, y = uniform scale
layout(location=5) in vec4 aInstColor;    // rgb = tint/shirt, a > 0 mixes with aBaseColor

uniform mat4 uViewProj;
out vec3 vNormal;
out vec3 vColor;
out vec3 vWorldPos;

void main() {
  float c = cos(aInstYawScale.x);
  float s = sin(aInstYawScale.x);
  vec3 p = aPos * aInstYawScale.y;
  vec3 rotPos = vec3(p.x * c + p.z * s, p.y, -p.x * s + p.z * c) + aInstPos;
  vec3 rotNorm = vec3(aNormal.x * c + aNormal.z * s, aNormal.y, -aNormal.x * s + aNormal.z * c);
  vWorldPos = rotPos;
  vNormal = rotNorm;
  vColor = mix(aBaseColor, aInstColor.rgb, step(0.0, -aBaseColor.r) * aInstColor.a);
  gl_Position = uViewProj * vec4(rotPos, 1.0);
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

void main() {
  vec3 n = normalize(vNormal);
  float ndotl = max(dot(n, normalize(uSunDir)), 0.0);
  vec3 ambient = mix(uGroundColor, uSkyColor, n.y * 0.5 + 0.5);
  float nightPop = uLightMode > 1.5 ? 0.22 : 0.06;
  vec3 col = vColor * (ambient * 0.58 + uSunColor * (ndotl * 0.65 + 0.35) + nightPop);
  fragColor = vec4(col, 1.0);
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
