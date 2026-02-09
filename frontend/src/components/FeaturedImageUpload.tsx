/**
 * FeaturedImageUpload — Reusable featured image upload/delete component.
 * Matches Laravel's polymorphic images() relationship + Storage upload.
 */
import { useState, useEffect, useRef } from 'react';
import { Button } from '@/components/ui/button';
import { uploadFeaturedImage, deleteFeaturedImage, getFeaturedImageUrl } from '@/lib/api';
import { ImagePlus, Trash2, Loader2 } from 'lucide-react';
import { toast } from 'sonner';

interface FeaturedImageUploadProps {
  entityType: 'course' | 'lesson' | 'topic' | 'quiz';
  entityId: number;
  className?: string;
}

export function FeaturedImageUpload({ entityType, entityId, className = '' }: FeaturedImageUploadProps) {
  const [imageUrl, setImageUrl] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [uploading, setUploading] = useState(false);
  const [deleting, setDeleting] = useState(false);
  const fileInputRef = useRef<HTMLInputElement>(null);

  useEffect(() => {
    setLoading(true);
    getFeaturedImageUrl(entityType, entityId)
      .then((url) => setImageUrl(url))
      .finally(() => setLoading(false));
  }, [entityType, entityId]);

  const handleUpload = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;

    // Validate file type
    const validTypes = ['image/jpeg', 'image/jpg', 'image/png'];
    if (!validTypes.includes(file.type)) {
      toast.error('Only JPG and PNG files are allowed');
      return;
    }

    setUploading(true);
    try {
      const result = await uploadFeaturedImage(entityType, entityId, file);
      setImageUrl(result.url);
      toast.success('Image uploaded successfully');
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to upload image');
    } finally {
      setUploading(false);
      if (fileInputRef.current) fileInputRef.current.value = '';
    }
  };

  const handleDelete = async () => {
    if (!confirm('Delete this featured image?')) return;
    setDeleting(true);
    try {
      await deleteFeaturedImage(entityType, entityId);
      setImageUrl(null);
      toast.success('Image deleted');
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to delete image');
    } finally {
      setDeleting(false);
    }
  };

  if (loading) {
    return (
      <div className={`flex items-center justify-center h-24 bg-[#f8fafc] rounded-lg border border-dashed border-[#e2e8f0] ${className}`}>
        <Loader2 className="w-5 h-5 animate-spin text-[#94a3b8]" />
      </div>
    );
  }

  return (
    <div className={className}>
      <input
        ref={fileInputRef}
        type="file"
        accept="image/jpeg,image/jpg,image/png"
        className="hidden"
        onChange={handleUpload}
      />

      {imageUrl ? (
        <div className="relative group">
          <img
            src={imageUrl}
            alt="Featured"
            className="w-full h-32 object-cover rounded-lg border border-[#e2e8f0]"
          />
          <div className="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity rounded-lg flex items-center justify-center gap-2">
            <Button
              size="sm"
              variant="secondary"
              className="h-8 text-xs"
              disabled={uploading}
              onClick={() => fileInputRef.current?.click()}
            >
              {uploading ? <Loader2 className="w-3.5 h-3.5 animate-spin" /> : <ImagePlus className="w-3.5 h-3.5 mr-1" />}
              Replace
            </Button>
            <Button
              size="sm"
              variant="destructive"
              className="h-8 text-xs"
              disabled={deleting}
              onClick={handleDelete}
            >
              {deleting ? <Loader2 className="w-3.5 h-3.5 animate-spin" /> : <Trash2 className="w-3.5 h-3.5 mr-1" />}
              Remove
            </Button>
          </div>
        </div>
      ) : (
        <button
          type="button"
          className="w-full h-24 bg-[#f8fafc] rounded-lg border border-dashed border-[#e2e8f0] hover:border-[#3b82f6] hover:bg-[#eff6ff] transition-colors flex flex-col items-center justify-center gap-1 cursor-pointer"
          disabled={uploading}
          onClick={() => fileInputRef.current?.click()}
        >
          {uploading ? (
            <Loader2 className="w-5 h-5 animate-spin text-[#3b82f6]" />
          ) : (
            <>
              <ImagePlus className="w-5 h-5 text-[#94a3b8]" />
              <span className="text-xs text-[#94a3b8]">Upload Featured Image</span>
            </>
          )}
        </button>
      )}
    </div>
  );
}
