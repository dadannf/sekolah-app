import os
import random
import shutil
from pathlib import Path

def split_dataset(dataset_dir: str, annotation_file: str, train_ratio=0.70, val_ratio=0.15):
    """
    Split the dataset into Train, Validation, and Test sets based on exact ratios or fixed counts.
    Target counts for 1560 images:
    - Train: 1092
    - Val: 234
    - Test: 234
    """
    dataset_path = Path(dataset_dir)
    anno_path = dataset_path / annotation_file
    
    if not anno_path.exists():
        print(f"Annotation file {anno_path} not found!")
        return

    # Read and parse annotation file
    with open(anno_path, 'r', encoding='utf-8') as f:
        lines = [line.strip() for line in f if line.strip()]

    print(f"Found {len(lines)} annotations in {annotation_file}")
    
    # Shuffle for random split
    random.seed(42)  # For reproducibility
    random.shuffle(lines)

    total_images = len(lines)
    # Based on user requirement: 1092 train, 234 val, 234 test out of 1560. 
    # If the total is slightly different (e.g., 1561), we compute exact indexes based on ratio.
    train_count = int(total_images * train_ratio)
    val_count = int(total_images * val_ratio)
    
    # Alternatively, force exact counts if the dataset is exactly 1560 or 1561.
    if total_images >= 1560:
        train_count = 1092
        val_count = 234
        # test will be the remainder

    train_lines = lines[:train_count]
    val_lines = lines[train_count:train_count + val_count]
    test_lines = lines[train_count + val_count:]

    print(f"Split distribution -> Train: {len(train_lines)}, Val: {len(val_lines)}, Test: {len(test_lines)}")

    # Create subdirectories
    for split in ['train', 'val', 'test']:
        split_dir = dataset_path / split
        split_dir.mkdir(exist_ok=True)

    def process_split(split_name, split_data):
        out_txt = dataset_path / f"rec_gt_{split_name}.txt"
        new_lines = []
        
        copied_count = 0
        missing_count = 0

        for line in split_data:
            # Format usually: crop_img/filename.jpg<TAB>Label
            if '\t' in line:
                img_rel_path, label = line.split('\t', 1)
            else:
                # Some files might use space
                parts = line.split(' ', 1)
                if len(parts) == 2:
                    img_rel_path, label = parts
                else:
                    print(f"Skipping malformed line: {line}")
                    continue

            src_img_path = dataset_path / img_rel_path
            if not src_img_path.exists():
                print(f"Image not found: {src_img_path}")
                missing_count += 1
                continue

            img_filename = src_img_path.name
            dest_img_path = dataset_path / split_name / img_filename
            
            # Copy image
            shutil.copy2(src_img_path, dest_img_path)
            copied_count += 1
            
            # Write new line with updated path (e.g., train/filename.jpg<TAB>Label)
            new_lines.append(f"{split_name}/{img_filename}\t{label}\n")

        with open(out_txt, 'w', encoding='utf-8') as f:
            f.writelines(new_lines)
            
        print(f"[{split_name.upper()}] Copied {copied_count} images. Missing: {missing_count}. Annotation written to {out_txt.name}")

    # Process all splits
    process_split('train', train_lines)
    process_split('val', val_lines)
    process_split('test', test_lines)

if __name__ == '__main__':
    # Use absolute path based on user's environment
    target_dataset_dir = r"F:\laragon\www\sekolah\training\dataset"
    target_annotation = "rec_gt.txt"
    split_dataset(target_dataset_dir, target_annotation)
    print("Dataset splitting completed successfully.")
