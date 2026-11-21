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
uniform float uContours;  // 0=off, 1=steepness/contour boost
uniform float uNightLit;  // >0 warms lower village & base lifts at night

out vec4 fragColor;

void main() {
  vec3 texCol = texture(uMapTex, vUv).rgb;
  vec3 n = normalize(vNormal);
  float ndotl = max(dot(n, normalize(uSunDir)), 0.0);
  float hemi = n.y * 0.5 + 0.5;
  vec3 ambient = mix(uGroundColor, uSkyColor, hemi);
  vec3 lit = texCol * (ambient + uSunColor * ndotl);

  // Topographic elevation contour lines (emphasized in Steepness lens)
  float stepY = 5.0;
  float modY = fract(vWorldPos.y / stepY);
  float lineMask = 1.0 - smoothstep(0.0, 0.09, min(modY, 1.0 - modY));
  float contourMix = uContours > 0.5 ? lineMask * 0.34 : lineMask * 0.07;
  vec3 contourCol = uLightMode > 1.5 ? vec3(0.32, 0.48, 0.72) : vec3(0.16, 0.25, 0.38);
  lit = mix(lit, contourCol, contourMix);

  if (uContours > 0.5) {
    // Soft alpine gradient tint by terrain slope steepness
    float t = clamp((vSlopeDeg - 8.0) / 34.0, 0.0, 1.0);
    vec3 gradeCol = mix(vec3(0.22, 0.74, 0.44), mix(vec3(0.26, 0.54, 0.94), vec3(0.92, 0.36, 0.24), clamp((t - 0.45) * 1.8, 0.0, 1.0)), clamp(t * 1.4, 0.0, 1.0));
    lit = mix(lit, gradeCol * (0.55 + 0.45 * ndotl), 0.28);
  }

  // Warm alpine night floodlight & village glow when night lighting is active
  if (uLightMode > 1.5 && uNightLit > 0.05) {
    float baseGlow = exp(-pow((vWorldPos.z - 270.0) / 85.0, 2.0)) * 0.32;
    lit += vec3(0.95, 0.65, 0.28) * baseGlow * uNightLit;
  }

  float dist = length(uCameraPos - vWorldPos);
  float fog = clamp((dist - 420.0) / 760.0, 0.0, 0.55);
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
  float ndotl = max(dot(n, normalize(uSunDir)), 0.0);
  float hemi = n.y * 0.5 + 0.5;
  vec3 ambient = mix(uGroundColor, uSkyColor, hemi);
  float emissiveBoost = uLightMode > 1.5 ? max(0.35, vColor.a * 1.4) : vColor.a * 0.65;
  vec3 base = vColor.rgb * (ambient + uSunColor * (ndotl * 0.88 + 0.14)) + vColor.rgb * emissiveBoost;

  float dist = length(uCameraPos - vWorldPos);
  float fog = clamp((dist - 460.0) / 820.0, 0.0, 0.45);
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
  // Negative aBaseColor signal encodes parts that take aInstColor (e.g., skier jacket)
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
  float nightPop = uLightMode > 1.5 ? 0.25 : 0.08;
  vec3 col = vColor * (ambient * 1.15 + uSunColor * (ndotl * 0.85 + 0.2) + nightPop);
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
