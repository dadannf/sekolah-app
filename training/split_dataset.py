import os
import random
import shutil

def split_dataset(base_dir, out_dir, ratio=0.8):
    # det
    label_file = os.path.join(base_dir, 'Label.txt')
    # rec
    rec_file = os.path.join(base_dir, 'rec_gt.txt')
    
    # Check if files exist
    if not os.path.exists(label_file) or not os.path.exists(rec_file):
        print("Error: Label.txt or rec_gt.txt not found in", base_dir)
        return

    # Process Det
    with open(label_file, 'r', encoding='utf-8') as f:
        det_lines = f.readlines()
    
    random.seed(42) # for reproducibility
    random.shuffle(det_lines)
    split_idx = int(len(det_lines) * ratio)
    det_train = det_lines[:split_idx]
    det_val = det_lines[split_idx:]
    
    # Create Det dirs
    det_train_dir = os.path.join(out_dir, 'det_data', 'train')
    det_val_dir = os.path.join(out_dir, 'det_data', 'val')
    os.makedirs(det_train_dir, exist_ok=True)
    os.makedirs(det_val_dir, exist_ok=True)
    
    # Helper to copy det images and update paths
    def process_det_lines(lines, out_img_dir, out_txt_path, prefix_dir):
        new_lines = []
        for line in lines:
            parts = line.strip().split('\t')
            if len(parts) < 2:
                continue
            img_path = parts[0]
            label = parts[1]
            
            img_basename = os.path.basename(img_path)
            src_img = os.path.join(base_dir, img_path)
            if not os.path.exists(src_img):
                src_img = os.path.join(base_dir, img_basename)
                
            if os.path.exists(src_img):
                shutil.copy(src_img, os.path.join(out_img_dir, img_basename))
                # For PaddleOCR, path in txt usually includes the folder name, e.g. "train/image.jpg"
                new_path = f"{prefix_dir}/{img_basename}"
                new_lines.append(f"{new_path}\t{label}\n")
            else:
                print(f"Warning: missing image {src_img}")
        
        with open(out_txt_path, 'w', encoding='utf-8') as f:
            f.writelines(new_lines)
            
    process_det_lines(det_train, det_train_dir, os.path.join(out_dir, 'det_data', 'train.txt'), 'train')
    process_det_lines(det_val, det_val_dir, os.path.join(out_dir, 'det_data', 'val.txt'), 'val')
    print("Det dataset splitted.")

    # Process Rec
    with open(rec_file, 'r', encoding='utf-8') as f:
        rec_lines = f.readlines()
        
    random.shuffle(rec_lines)
    split_idx_rec = int(len(rec_lines) * ratio)
    rec_train = rec_lines[:split_idx_rec]
    rec_val = rec_lines[split_idx_rec:]
    
    rec_train_dir = os.path.join(out_dir, 'rec_data', 'train')
    rec_val_dir = os.path.join(out_dir, 'rec_data', 'val')
    os.makedirs(rec_train_dir, exist_ok=True)
    os.makedirs(rec_val_dir, exist_ok=True)
    
    def process_rec_lines(lines, out_img_dir, out_txt_path, prefix_dir):
        new_lines = []
        for line in lines:
            parts = line.strip().split('\t')
            if len(parts) < 2:
                continue
            img_path = parts[0]
            label = parts[1]
            
            img_basename = os.path.basename(img_path)
            src_img = os.path.join(base_dir, img_path)
            if not os.path.exists(src_img):
                src_img = os.path.join(base_dir, 'crop_img', img_basename)
                
            if os.path.exists(src_img):
                shutil.copy(src_img, os.path.join(out_img_dir, img_basename))
                new_path = f"{prefix_dir}/{img_basename}"
                new_lines.append(f"{new_path}\t{label}\n")
            else:
                print(f"Warning: missing image {src_img}")
                
        with open(out_txt_path, 'w', encoding='utf-8') as f:
            f.writelines(new_lines)
            
    process_rec_lines(rec_train, rec_train_dir, os.path.join(out_dir, 'rec_data', 'train.txt'), 'train')
    process_rec_lines(rec_val, rec_val_dir, os.path.join(out_dir, 'rec_data', 'val.txt'), 'val')
    print("Rec dataset splitted.")

if __name__ == '__main__':
    base_dir = r"f:\laragon\www\sekolah\training\dataset"
    out_dir = r"f:\laragon\www\sekolah\training\train_data"
    split_dataset(base_dir, out_dir, ratio=0.8)
    print("Dataset splitting completed successfully.")
