<?php

namespace App\Controller;

use App\Repository\DefiRepository;
use App\Repository\ParticipationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

class AnalyticsController extends AbstractController
{
    #[Route('/admin/challenges/analytics', name: 'admin_challenges_analytics', methods: ['GET'])]
    public function challengesAnalytics(DefiRepository $defiRepository, ParticipationRepository $participationRepository): Response
    {
        $defis = $defiRepository->findAll();
        
        $defisByDate = [];
        $participationsByDate = [];
        $participationsByDefi = [];
        $themesPerformance = [];
        
        foreach ($defis as $defi) {
            if ($defi->getDateDebut()) {
                $dateKey = $defi->getDateDebut()->format('Y-m-d');
                if (!isset($defisByDate[$dateKey])) {
                    $defisByDate[$dateKey] = 0;
                }
                $defisByDate[$dateKey]++;
            }
            
            // Theme performance tracking
            $theme = $defi->getTheme() ?? 'Non defini';
            if (!isset($themesPerformance[$theme])) {
                $themesPerformance[$theme] = ['defis' => 0, 'participations' => 0, 'avg' => 0];
            }
            $themesPerformance[$theme]['defis']++;
            $themesPerformance[$theme]['participations'] += $defi->getParticipations()->count();
            
            $participationsByDefi[$defi->getId()] = [
                'titre' => $defi->getTitre(),
                'theme' => $theme,
                'count' => $defi->getParticipations()->count(),
                'statut' => $defi->getStatut()->value,
                'dateDebut' => $defi->getDateDebut()
            ];
            
            foreach ($defi->getParticipations() as $participation) {
                if ($participation->getDateSoumission()) {
                    $partDateKey = $participation->getDateSoumission()->format('Y-m-d');
                    if (!isset($participationsByDate[$partDateKey])) {
                        $participationsByDate[$partDateKey] = 0;
                    }
                    $participationsByDate[$partDateKey]++;
                }
            }
        }
        
        // Calculate theme averages
        foreach ($themesPerformance as $theme => $data) {
            $themesPerformance[$theme]['avg'] = $data['defis'] > 0 ? round($data['participations'] / $data['defis'], 1) : 0;
        }
        
        $totalDefis = count($defis);
        $totalParticipations = array_sum(array_column($participationsByDefi, 'count'));
        $defisOuverts = count(array_filter($defis, fn($d) => $d->getStatut()->value === 'OUVERT'));
        $defisFermes = count(array_filter($defis, fn($d) => $d->getStatut()->value === 'FERME' || $d->getStatut()->value === 'FERMEE'));
        
        // Sort by participations count descending (highest first)
        uasort($participationsByDefi, fn($a, $b) => $b['count'] <=> $a['count']);
        $topDefis = array_slice($participationsByDefi, 0, 5, true);
        
        // AI Analysis & Predictions
        $aiInsights = [];
        $predictions = [];
        $recommendations = [];
        $trendAnalysis = [];
        
        if ($totalDefis > 0) {
            $avgParticipation = $totalParticipations / $totalDefis;
            $aiInsights[] = "📊 En moyenne, chaque defi reçoit <strong>" . round($avgParticipation, 1) . "</strong> participations";
            
            if ($defisOuverts > $defisFermes) {
                $aiInsights[] = "🚀 <strong>{$defisOuverts}</strong> defis sont actuellement ouverts";
            }
            
            if (!empty($topDefis) && is_array($topDefis)) {
                $firstDefi = reset($topDefis);
                if ($firstDefi !== false && isset($firstDefi['count']) && $firstDefi['count'] > 0) {
                    $aiInsights[] = "🏆 Le defi le plus populaire: <strong>" . $firstDefi['titre'] . "</strong> avec <strong>" . $firstDefi['count'] . "</strong> participations";
                }
            }
            
            if ($totalParticipations > 10) {
                $aiInsights[] = "💡 Avec <strong>{$totalParticipations}</strong> participations totales, l'engagement est fort";
            }
            
            // Trend Analysis
            if (!empty($participationsByDate) && is_array($participationsByDate)) {
                $recentParticipation = array_slice($participationsByDate, -7, 7, true);
                $earlierParticipation = array_slice($participationsByDate, -14, 7, true);
                
                $recentAvg = count($recentParticipation) > 0 ? array_sum($recentParticipation) / count($recentParticipation) : 0;
                $earlierAvg = count($earlierParticipation) > 0 ? array_sum($earlierParticipation) / count($earlierParticipation) : 0;
                
                if ($recentAvg > $earlierAvg * 1.2) {
                    $trendAnalysis['direction'] = 'up';
                    $trendAnalysis['message'] = '📈 Tendance HAUSSIERE: Les participations augmentent de ' . round(($recentAvg / max($earlierAvg, 1) - 1) * 100) . '% cette semaine!';
                } elseif ($recentAvg < $earlierAvg * 0.8 && $earlierAvg > 0) {
                    $trendAnalysis['direction'] = 'down';
                    $trendAnalysis['message'] = '📉 Tendance BAISSIERE: Les participations ont diminue de ' . round((1 - $recentAvg / max($earlierAvg, 1)) * 100) . '% cette semaine';
                } else {
                    $trendAnalysis['direction'] = 'stable';
                    $trendAnalysis['message'] = '➡️ Tendance STABLE: Les participations restent constantes';
                }
            }
            
            // Predictions
            if ($totalDefis >= 3 && $avgParticipation > 0) {
                $nextMonthProjection = round($defisOuverts * $avgParticipation * 1.2);
                $predictions[] = "🔮 Projection: <strong>{$nextMonthProjection}</strong> participations attendues pour les defis ouverts";
                
                if (!empty($participationsByDate)) {
                    $maxVal = !empty($participationsByDate) ? max($participationsByDate) : 0;
                    if ($maxVal > 0) {
                        $bestDay = array_keys($participationsByDate, $maxVal);
                        $predictions[] = "📅 Jour optimal: Le plus de participations enregistrees le <strong>" . (isset($bestDay[0]) ? date('l', strtotime($bestDay[0])) : 'N/A') . "</strong>";
                    }
                }
            }
            
            // Recommendations based on data
            $lowPerforming = array_filter($participationsByDefi, fn($d) => $d['count'] < $avgParticipation && $d['statut'] === 'OUVERT');
            if (!empty($lowPerforming)) {
                $recommendations[] = "⚠️ <strong>" . count($lowPerforming) . "</strong> defi(s) ouvert(s) pourraient beneficier de plus de visibilite";
            }
            
            // Best performing theme
            if (!empty($themesPerformance) && is_array($themesPerformance)) {
                $sortedThemes = $themesPerformance;
                uasort($sortedThemes, fn($a, $b) => $b['avg'] <=> $a['avg']);
                $bestTheme = array_key_first($sortedThemes);
                if ($bestTheme) {
                    $recommendations[] = "🎯 Le theme <strong>{$bestTheme}</strong> performe le mieux avec en moyenne {$sortedThemes[$bestTheme]['avg']} participations";
                }
            }
            
            // Timing recommendation
            $currentHour = (int)date('H');
            if ($currentHour >= 18 && $currentHour <= 22) {
                $recommendations[] = "🌙 Les heures actuelles (18h-22h) sont optimales pour poster";
            }
            
            if ($defisOuverts === 0 && $totalDefis > 0) {
                $recommendations[] = "⏰ Aucun defi ouvert! Creez-en un nouveau pour maintenir l'engagement";
            }
        }
        
        // Top themes
        if (!empty($themesPerformance) && is_array($themesPerformance)) {
            $sortedThemes = $themesPerformance;
            uasort($sortedThemes, fn($a, $b) => $b['participations'] <=> $a['participations']);
            $topThemes = array_slice($sortedThemes, 0, 3, true);
            $worstThemes = array_slice($sortedThemes, -2, 2, true);
        } else {
            $topThemes = [];
            $worstThemes = [];
        }
        
        ksort($defisByDate);
        ksort($participationsByDate);
        
        return $this->render('admin/challenges_analytics.html.twig', [
            'totalDefis' => $totalDefis,
            'totalParticipations' => $totalParticipations,
            'defisOuverts' => $defisOuverts,
            'defisFermes' => $defisFermes,
            'defisByDate' => $defisByDate,
            'participationsByDate' => $participationsByDate,
            'participationsByDefi' => $participationsByDefi,
            'topDefis' => $topDefis,
            'aiInsights' => $aiInsights,
            'predictions' => $predictions,
            'recommendations' => $recommendations,
            'trendAnalysis' => $trendAnalysis,
            'topThemes' => $topThemes,
            'worstThemes' => $worstThemes,
            'themesPerformance' => $themesPerformance,
            'avgParticipation' => isset($avgParticipation) ? round($avgParticipation, 1) : 0,
        ]);
    }
}
