from diffusers import StableDiffusionImg2ImgPipeline, EulerDiscreteScheduler
from PIL import Image
import torch
import sys
from themes_prompts import THEME_PROMPTS

# ======================
# Arguments
# python generate_image.py input.png output.png theme
# ======================
if len(sys.argv) < 4:
    print("Usage: python generate_image.py <input_image> <output_image> <theme>")
    sys.exit(1)

input_path = sys.argv[1]
output_path = sys.argv[2]
theme = sys.argv[3]

if theme not in THEME_PROMPTS:
    print("INVALID_THEME")
    sys.exit(1)

# ======================
# PROMPT FINAL (FORCÉ)
# ======================
prompt = (
    "full body transformation, complete environment replacement, "
    "new cinematic background, dramatic lighting, "
    + THEME_PROMPTS[theme]
)

negative_prompt = (
    "original background, photo background, real room, modern clothes, casual outfit, "
    "blurry, low quality, jpeg artifacts, watermark, text, logo, "
    "deformed face, bad anatomy, extra limbs"
)

# ======================
# Image utilisateur
# ======================
init_image = Image.open(input_path).convert("RGB").resize((768, 768))

# ======================
# Pipeline CPU
# ======================
pipe = StableDiffusionImg2ImgPipeline.from_pretrained(
    "runwayml/stable-diffusion-v1-5",
    torch_dtype=torch.float32,
    safety_checker=None,
    feature_extractor=None
)

pipe.scheduler = EulerDiscreteScheduler.from_config(pipe.scheduler.config)
pipe.to("cpu")

# ======================
# GÉNÉRATION (CLÉ DU SUCCÈS)
# ======================
with torch.no_grad():
    result = pipe(
        prompt=prompt,
        negative_prompt=negative_prompt,
        image=init_image,
        strength=0.75,              # 🔥 OBLIGATOIRE pour changer décor + costume
        guidance_scale=9.0,         # 🔥 force le thème
        num_inference_steps=28      # qualité stable CPU
    )

result.images[0].save(output_path)

print("OK_IMAGE_GENERATED")
print(output_path)