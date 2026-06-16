import cv2
import numpy as np
import os
import random
import glob
from pathlib import Path
import sys

def adjust_brightness(img, factor):
    hsv = cv2.cvtColor(img, cv2.COLOR_BGR2HSV)
    hsv = np.array(hsv, dtype=np.float64)
    hsv[:, :, 2] = hsv[:, :, 2] * factor
    hsv[:, :, 2][hsv[:, :, 2] > 255] = 255
    hsv = np.array(hsv, dtype=np.uint8)
    return cv2.cvtColor(hsv, cv2.COLOR_HSV2BGR)

def adjust_contrast(img, factor):
    img_float = img.astype(np.float64)
    img_float = 128 + factor * (img_float - 128)
    img_float = np.clip(img_float, 0, 255).astype(np.uint8)
    return img_float

def add_gaussian_noise(img):
    row, col, ch = img.shape
    mean = 0
    var = random.uniform(10, 30) # Reduced variance for less extreme noise
    sigma = var ** 0.5
    gauss = np.random.normal(mean, sigma, (row, col, ch))
    gauss = gauss.reshape(row, col, ch)
    noisy = img.astype(np.float64) + gauss
    noisy = np.clip(noisy, 0, 255).astype(np.uint8)
    return noisy

def apply_motion_blur(img):
    # Generating the kernel
    size = random.choice([3, 5])
    kernel = np.zeros((size, size))
    # Randomly select a direction
    direction = random.choice(['horizontal', 'vertical', 'diagonal'])
    if direction == 'horizontal':
        kernel[int((size-1)/2), :] = np.ones(size)
    elif direction == 'vertical':
        kernel[:, int((size-1)/2)] = np.ones(size)
    else:
        np.fill_diagonal(kernel, 1)
    
    kernel = kernel / size
    # Applying the kernel to the input image
    output = cv2.filter2D(img, -1, kernel)
    return output

def rotate_image(img, angle):
    # Max rotation should be less than or equal to 15 degrees
    (h, w) = img.shape[:2]
    center = (w // 2, h // 2)
    M = cv2.getRotationMatrix2D(center, angle, 1.0)
    
    # Calculate bounding box for rotated image to avoid aggressive cropping
    abs_cos = abs(M[0, 0])
    abs_sin = abs(M[0, 1])
    bound_w = int(h * abs_sin + w * abs_cos)
    bound_h = int(h * abs_cos + w * abs_sin)
    M[0, 2] += bound_w / 2 - center[0]
    M[1, 2] += bound_h / 2 - center[1]
    
    # We use INTER_CUBIC and a constant border to avoid losing characters
    rotated = cv2.warpAffine(img, M, (bound_w, bound_h), flags=cv2.INTER_CUBIC, borderMode=cv2.BORDER_REPLICATE)
    return rotated

def augment_image(img_path, out_dir, num_augments=2):
    img = cv2.imread(img_path)
    if img is None:
        print(f"Failed to read {img_path}")
        return []
        
    base_name = os.path.splitext(os.path.basename(img_path))[0]
    generated_files = []
    
    for i in range(num_augments):
        aug_img = img.copy()
        
        # 1. Random Brightness (±20%)
        if random.random() < 0.5:
            b_factor = random.uniform(0.8, 1.2)
            aug_img = adjust_brightness(aug_img, b_factor)
        
        # 2. Random Contrast (±20%)
        if random.random() < 0.5:
            c_factor = random.uniform(0.8, 1.2)
            aug_img = adjust_contrast(aug_img, c_factor)
        
        # 3. Gaussian Noise
        if random.random() < 0.3:
            aug_img = add_gaussian_noise(aug_img)
            
        # 4. Motion Blur (Handphone blur simulation)
        if random.random() < 0.3:
            aug_img = apply_motion_blur(aug_img)
            
        # 5. Random Rotation (-15 to +15 degrees max)
        if random.random() < 0.5:
            angle = random.uniform(-15, 15)
            aug_img = rotate_image(aug_img, angle)
        
        out_name = f"{base_name}_aug_{i+1:03d}.jpg"
        out_path = os.path.join(out_dir, out_name)
        cv2.imwrite(out_path, aug_img)
        generated_files.append(out_name)
        
    return generated_files

def main():
    # Only augment the 'train' folder
    source_dir = r"F:\laragon\www\sekolah\training\dataset\train"
    annotation_file = r"F:\laragon\www\sekolah\training\dataset\rec_gt_train.txt"
    
    if not os.path.exists(source_dir):
        print(f"Error: {source_dir} not found. Did you run split_dataset.py?")
        return
        
    if not os.path.exists(annotation_file):
        print(f"Error: {annotation_file} not found.")
        return
        
    # Read existing annotations
    with open(annotation_file, 'r', encoding='utf-8') as f:
        lines = [line.strip() for line in f if line.strip()]
        
    print(f"Found {len(lines)} original training images in {annotation_file}.")
    
    # To parse existing lines easily
    annotation_map = {}
    for line in lines:
        if '\t' in line:
            path, label = line.split('\t', 1)
        else:
            parts = line.split(' ', 1)
            if len(parts) == 2:
                path, label = parts
            else:
                continue
        # Only keep the filename from the path
        filename = os.path.basename(path)
        annotation_map[filename] = label

    # We will process each image and add augmented images to the text file
    image_paths = glob.glob(os.path.join(source_dir, "*.*"))
    image_paths = [p for p in image_paths if p.lower().endswith(('.png', '.jpg', '.jpeg')) and '_aug_' not in p]
    
    # Let's say we want 2 augmentations per image, tripling the training set size to ~3276
    num_augments_per_image = 2
    
    print(f"Applying augmentations to {len(image_paths)} images...")
    
    new_annotations = []
    
    for idx, img_path in enumerate(image_paths):
        filename = os.path.basename(img_path)
        if filename not in annotation_map:
            continue
            
        label = annotation_map[filename]
        
        # Generate augmented images (saved to same train directory)
        aug_filenames = augment_image(img_path, source_dir, num_augments=num_augments_per_image)
        
        for aug_file in aug_filenames:
            # Add to new annotations
            new_annotations.append(f"train/{aug_file}\t{label}\n")
            
        if (idx + 1) % 100 == 0:
            print(f"Processed {idx + 1}/{len(image_paths)} images...")
            
    # Append new annotations to the train annotation file
    if new_annotations:
        with open(annotation_file, 'a', encoding='utf-8') as f:
            f.writelines(new_annotations)
            
    print(f"Done! Generated {len(new_annotations)} augmented images.")
    print(f"Total training dataset size is now {len(lines) + len(new_annotations)}.")

if __name__ == "__main__":
    main()
