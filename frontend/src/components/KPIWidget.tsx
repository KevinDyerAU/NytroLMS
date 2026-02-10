/**
 * KPIWidget - Dashboard metric card matching NytroAI design
 * White card with icon, value, label, and optional trend/subtitle
 */
import React from 'react';
import { Link } from 'react-router-dom';
import { cn } from '@/lib/utils';
import { TrendingUp, TrendingDown, type LucideIcon } from 'lucide-react';

interface KPIWidgetProps {
  label: string;
  value: string | number;
  icon: LucideIcon;
  trend?: { value: number; label: string };
  subtitle?: string;
  color?: 'blue' | 'teal' | 'amber' | 'red' | 'green' | 'purple' | 'orange';
  link?: string;
}

const colorMap = {
  blue: { bg: 'bg-[#eff6ff]', icon: 'text-[#3b82f6]' },
  teal: { bg: 'bg-[#f0fdfa]', icon: 'text-[#14b8a6]' },
  amber: { bg: 'bg-[#fffbeb]', icon: 'text-[#f59e0b]' },
  red: { bg: 'bg-[#fef2f2]', icon: 'text-[#ef4444]' },
  green: { bg: 'bg-[#f0fdf4]', icon: 'text-[#22c55e]' },
  purple: { bg: 'bg-[#f5f3ff]', icon: 'text-[#8b5cf6]' },
  orange: { bg: 'bg-[#fff7ed]', icon: 'text-[#f97316]' },
};

export function KPIWidget({ label, value, icon: Icon, trend, subtitle, color = 'blue', link }: KPIWidgetProps) {
  const colors = colorMap[color];

  const content = (
    <div className="bg-white rounded-xl p-5 shadow-card border border-[#3b82f6]/20 hover:shadow-md transition-shadow duration-200 h-full">
      <div className="flex items-start justify-between">
        <div className="space-y-2">
          <p className="text-sm font-medium text-[#64748b]">{label}</p>
          <p className="text-2xl font-bold text-[#1e293b] font-heading">{value}</p>
          {trend && (
            <div className="flex items-center gap-1">
              {trend.value >= 0 ? (
                <TrendingUp className="w-3.5 h-3.5 text-[#22c55e]" />
              ) : (
                <TrendingDown className="w-3.5 h-3.5 text-[#ef4444]" />
              )}
              <span className={cn(
                "text-xs font-medium",
                trend.value >= 0 ? "text-[#22c55e]" : "text-[#ef4444]"
              )}>
                {trend.value >= 0 ? '+' : ''}{trend.value}%
              </span>
              <span className="text-xs text-[#94a3b8]">{trend.label}</span>
            </div>
          )}
          {subtitle && !trend && (
            <p className="text-xs text-[#94a3b8]">{subtitle}</p>
          )}
        </div>
        <div className={cn("p-2.5 rounded-lg", colors.bg)}>
          <Icon className={cn("w-5 h-5", colors.icon)} />
        </div>
      </div>
    </div>
  );

  if (link) {
    return <Link to={link} className="block">{content}</Link>;
  }
  return content;
}
