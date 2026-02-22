# ===============================
# IMPORTS
# ===============================
from diffusers import StableDiffusionPipeline, StableDiffusionImg2ImgPipeline
import torch
from PIL import Image

# ===============================
# THEME PROMPTS (Costume + Background)
# ===============================
THEME_PROMPTS = {
    "fantasy": (
        "full body transformation, complete environment replacement, "
        "legendary high-fantasy warrior, ornate enchanted silver elven armor with glowing runes, flowing royal cape, magical sword, "
        "epic fantasy background with floating islands, crystal waterfalls, giant glowing world tree, rainbow sky, mystical fog, "
        "cinematic volumetric lighting, dramatic depth of field, ultra-detailed, 8K, masterpiece, best quality"
    ),
    "anime": (
        "full body transformation, complete environment replacement, "
        "anime-style traveler character, layered fantasy outfit with scarf flowing in the wind, leather boots, glowing magical accessories, "
        "breathtaking anime city background with floating castle, vibrant sky, fluffy clouds, soft cel shading, cinematic Japanese animation style, ultra-detailed, masterpiece"
    ),
    "cyberpunk": (
        "full body transformation, complete environment replacement, "
        "cyberpunk augmented human, tactical armor with neon circuitry, holographic HUD, glowing implants, "
        "futuristic megacity night background, neon signs, rain-soaked streets, flying vehicles, teal and magenta lighting, ultra-detailed, masterpiece"
    ),
    "steampunk": (
        "full body transformation, complete environment replacement, "
        "Victorian-era explorer, brass-and-leather armor, mechanical shoulder plates, copper tubing, clockwork goggles, "
        "industrial steampunk city background with giant gears, airships, smokestacks, copper buildings, warm sepia cinematic lighting, ultra-detailed, masterpiece"
    ),
    "dark_fantasy": (
        "full body transformation, complete environment replacement, "
        "demonic dark knight with subtle horns, battle-worn obsidian armor, cursed greatsword, torn cape, "
        "ruined gothic castle background with blood-red moon, swirling ashes, dramatic storm clouds, high contrast lighting, ultra-detailed, masterpiece"
    )
}

# ===============================
# NEGATIVE PROMPT
# ===============================
NEGATIVE_PROMPT = (
    "blurry, low quality, deformed face, extra limbs, missing fingers, duplicate face, distorted eyes, artifacts, jpeg artifacts, "
    "watermark, text, logo, modern clothes, casual outfit, photo background"
)

# ===============================
# PROMPT GENERATOR
# ===============================
def generate_prompt(theme: str) -> dict:
    theme = theme.lower()
    base_prompt = THEME_PROMPTS.get(theme, THEME_PROMPTS["fantasy"])
    return {
        "prompt": base_prompt,
        "negative_prompt": NEGATIVE_PROMPT
    }

# ===============================
# GENERATE IMAGE (txt2img / img2img)
# ===============================
def generate_image(theme: str, init_image: Image.Image = None, strength: float = 0.75, steps: int = 30, device: str = "cuda"):
    """
    Génère une image avec costume + background selon le thème.
    Si init_image est fourni -> img2img
    Sinon -> txt2img
    """
    prompts = generate_prompt(theme)
    
    if init_image:
        # img2img : modifie l'image tout en appliquant le thème + costume + background
        pipe = StableDiffusionImg2ImgPipeline.from_pretrained(
            "runwayml/stable-diffusion-v1-5",
            torch_dtype=torch.float16 if device=="cuda" else torch.float32
        ).to(device)
        
        image = pipe(
            prompt=prompts["prompt"],
            negative_prompt=prompts["negative_prompt"],
            image=init_image,
            strength=strength,
            guidance_scale=9,
            num_inference_steps=steps
        ).images[0]
        
    else:
        # txt2img : création complète avec costume + background
        pipe = StableDiffusionPipeline.from_pretrained(
            "runwayml/stable-diffusion-v1-5",
            torch_dtype=torch.float16 if device=="cuda" else torch.float32
        ).to(device)
        
        image = pipe(
            prompt=prompts["prompt"],
            negative_prompt=prompts["negative_prompt"],
            width=768,
            height=1024,
            guidance_scale=9,
            num_inference_steps=steps
        ).images[0]
    
    return image

# ===============================
# EXEMPLE D'UTILISATION
# ===============================
if __name__ == "__main__":
    theme = "fantasy"  
    
    # Pour txt2img (création complète)
    result_img = generate_image(theme)
    
    # Pour img2img (modification d'une photo)
    # init_img = Image.open("ma_photo.png").convert("RGB")
    # result_img = generate_image(theme, init_image=init_img)
    
    result_img.save(f"output_{theme}.png")
    print(f"Image générée avec costume + background : output_{theme}.png")