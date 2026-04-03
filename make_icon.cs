using System;
using System.Drawing;
using System.Drawing.Imaging;
using System.Drawing.Drawing2D;
using System.IO;

class IconMaker {
    public static void Main() {
        try {
            string logoPath = @"public\images\smartspace-logo.png";
            string iconPath = @"public\app_icon.ico";
            
            if (!File.Exists(logoPath)) {
                Console.WriteLine("Logo not found at " + logoPath);
                return;
            }

            // Create a 256x256 PNG in memory with high quality
            byte[] pngData;
            using (Bitmap original = (Bitmap)Image.FromFile(logoPath)) {
                // Ensure square bounds 256x256
                int size = 256;
                using (Bitmap resized = new Bitmap(size, size, PixelFormat.Format32bppArgb)) {
                    using (Graphics g = Graphics.FromImage(resized)) {
                        g.InterpolationMode = InterpolationMode.HighQualityBicubic;
                        g.SmoothingMode = SmoothingMode.HighQuality;
                        g.PixelOffsetMode = PixelOffsetMode.HighQuality;
                        
                        // Calculate padding so it fits well inside the square
                        int pad = 16;
                        int targetWidth = size - pad * 2;
                        int targetHeight = size - pad * 2;
                        
                        float ratioX = (float)targetWidth / original.Width;
                        float ratioY = (float)targetHeight / original.Height;
                        float ratio = Math.Min(ratioX, ratioY);
                        
                        int newWidth = (int)(original.Width * ratio);
                        int newHeight = (int)(original.Height * ratio);
                        
                        int posX = (size - newWidth) / 2;
                        int posY = (size - newHeight) / 2;
                        
                        g.DrawImage(original, posX, posY, newWidth, newHeight);
                    }
                    
                    using (MemoryStream ms = new MemoryStream()) {
                        resized.Save(ms, ImageFormat.Png);
                        pngData = ms.ToArray();
                    }
                }
            }

            // Write ICO format directly
            using (FileStream fs = new FileStream(iconPath, FileMode.Create)) {
                using (BinaryWriter bw = new BinaryWriter(fs)) {
                    // Header
                    bw.Write((short)0); // reserved
                    bw.Write((short)1); // icon type
                    bw.Write((short)1); // count = 1

                    // Entry
                    bw.Write((byte)0); // width (0 = 256)
                    bw.Write((byte)0); // height (0 = 256)
                    bw.Write((byte)0); // colors
                    bw.Write((byte)0); // reserved
                    bw.Write((short)1); // color planes
                    bw.Write((short)32); // bpp
                    bw.Write((int)pngData.Length); // size of data
                    bw.Write((int)22); // offset (6 header + 16 entry = 22)

                    // Data
                    bw.Write(pngData);
                }
            }

            Console.WriteLine("Successfully created HD transparent icon at " + iconPath);
        } catch (Exception ex) {
            Console.WriteLine("Error: " + ex.Message);
        }
    }
}
