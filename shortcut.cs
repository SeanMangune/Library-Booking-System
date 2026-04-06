using System;
using System.Diagnostics;

class Program
{
    static void Main(string[] args)
    {
        try
        {
            Process.Start(new ProcessStartInfo
            {
                FileName = "http://smartspacelibrary.app",
                UseShellExecute = true
            });
        }
        catch (Exception ex)
        {
            // Ignore if fails
        }
    }
}
