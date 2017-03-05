<?php

class Schedule_model extends MY_Model{

    function get_schedule_data()
    {
        $data = $this->db->select('home.team_name as home_team, away.team_name as away_team, schedule_title_id')
                ->select('schedule.week, schedule.year, schedule.game')
                ->select('schedule.home_team_id, schedule.away_team_id, schedule.game_type_id')
                ->select('schedule_game_type.text_id as game_type')
                ->from('schedule')
                ->join('team as home', 'home.id = schedule.home_team_id', 'left')
                ->join('team as away', 'away.id = schedule.away_team_id', 'left')
                ->join('schedule_game_type', 'schedule_game_type.id = schedule.game_type_id', 'left')
                ->where('schedule.league_id', $this->leagueid)
                ->where('schedule.year', $this->current_year)
                ->order_by('schedule.week', 'asc')
                ->order_by('schedule.game', 'asc')
                ->get();

        return $data->result();
    }

    function get_game_types_data()
    {
        return $this->db->select('id, text_id, default')->from('schedule_game_type')
                ->where('league_id', $this->leagueid)->get()->result();
    }

    function get_teams_data()
    {
        $data = $this->db->select('team.id as team_id, team.team_name')
                ->select('division.id as division_id, division.name as division_name')
                ->from('team')
                ->join('team_division','team_division.team_id = team.id and team_division.year='.$this->current_year,'left')
                ->join('division','team_division.division_id = division.id', 'left')
                ->where('team.league_id',$this->leagueid)
                ->where('team.active',1)
                ->order_by('division.id','asc')
                ->get();

        return $data->result();
    }

    function get_divisions_data()
    {
        $data = $this->db->select('division.id, division.name')
                ->from('division')
                ->where('division.league_id', $this->leagueid)->get();

        return $data->result();
    }

    function save_template($data)
    {
        if(isset($data['id']))
            $this->db->where('id', $data['id'])->update('schedule_template', $data);
        else
            $this->db->insert('schedule_template',$data);
    }


    function get_templates_data()
    {
        return $this->db->select('*')->from('schedule_template')->get()->result();
    }

    function get_template_data($id)
    {
        return $this->db->select('*')->from('schedule_template')->where('id',$id)->get()->row();
    }

    function get_template_matchups_data($id)
    {
        return $this->db->select('*')->from('schedule_template_matchup')
                ->where('schedule_template_id', $id)
                ->order_by('week','asc')->order_by('game','asc')
                ->get()->result();
    }

    function save_template_matchups($id, $data)
    {
        $this->db->delete('schedule_template_matchup',
                array('schedule_template_id' => $id));
        $this->db->insert_batch('schedule_template_matchup', $data);
    }

    function delete_template($id)
    {
        $this->db->delete('schedule_template_matchup',
                array('schedule_template_id' => $id));
        $this->db->delete('schedule_template', array('id' => $id));
    }

    function delete_game($week, $game)
    {
        $this->db->delete('schedule', array('week' => $week,
                                            'game' => $game,
                                            'league_id' => $this->leagueid));
        redirect('admin/schedule/edit');
    }

    function add_games($week, $count)
    {
        $data = array();
        $game = $this->db->select('(max(game) +1) as next')->from('schedule')
                ->where('week', $week)->where('league_id',$this->leagueid)
                ->get()->row()->next;
        if ($game == null)
            $game = 1;
        for($i = 1; $i <= $count; $i++)
        {
            $data[] = array('week' => $week,
                            'game' => $game,
                            'year' => $this->current_year,
                            'league_id' => $this->leagueid,
                            'nfl_week_type_id' => $this->common_model->get_week_type_id());
            $game++;
        }
        $this->db->insert_batch('schedule', $data);
    }

    function create_schedule_from_template($template_id, $map)
    {
        $matchups = $this->db->select('week, game, home, away')
                ->from('schedule_template_matchup')
                ->where('schedule_template_id', $template_id)->get()->result();

        $row = $this->db->select('id')->from('schedule_game_type')
                ->where('league_id', $this->leagueid)
                ->order_by('default', 'desc')->get()->row();
        if (count($row) > 0)
            $default_type = $row->id;
        else
            $default_type = 0;
        $data = array();

        foreach($matchups as $m)
        {
            $data[] = array('home_team_id' => $map[$m->home],
                            'away_team_id' => $map[$m->away],
                            'game_type_id' => $default_type,
                            'week' => $m->week,
                            'year' => $this->current_year,
                            'league_id' => $this->leagueid,
                            'game' => $m->game,
                            'nfl_week_type_id' => $this->common_model->get_week_type_id());
        }
        $this->db->delete('schedule', array('league_id' => $this->leagueid, 'year' => $this->current_year));
        $this->db->insert_batch('schedule', $data);

    }


    function save_schedule($schedule)
    {
        foreach($schedule as $week_id => $week)
        {
            foreach($week as $game_id => $game)
            {
                if (array_key_exists('type',$game)){$game_type = $game['type'];}else{$game_type = 0;}
                if (array_key_exists('title',$game)){$game_title = $game['title'];}else{$game_title = 0;}
                $data = array('home_team_id' => $game['home'],
                              'away_team_id' => $game['away'],
                              'game_type_id' => $game_type,
                              'schedule_title_id' => $game_title);
                $this->db->where('week',$week_id)->where('game', $game_id)
                        ->where('league_id', $this->leagueid)->where('year', $this->current_year)
                        ->update('schedule', $data);
            }
        }
    }

    function get_gametypes_data()
    {
        return $this->db->select('id, text_id, default, title_game')->from('schedule_game_type')
                ->where('league_id',$this->leagueid)->get()->result();
    }

    function get_gametype_data($id)
    {
        return $this->db->select('id, text_id, default, for_title')->from('schedule_game_type')
                ->where('league_id',$this->leagueid)->where('id',$id)->get()->row();
    }

    function get_titles_data()
    {
        return $this->db->select('id, text, display_order')
            ->from('schedule_title')->where('league_id',$this->leagueid)->get()->result();
    }

    function add_gametype($id, $title_game)
    {
        $this->db->insert('schedule_game_type', array('text_id' => $id,
                                                    'league_id' => $this->leagueid,
                                                    'title_game' => $title_game,
                                                    'default' => 0));
    }

    function set_default_gametype($id)
    {
        $this->db->where('id', $id)->where('league_id', $this->leagueid)
                ->update('schedule_game_type', array('default' => 1));
        $this->db->where('id !=', $id)->where('league_id', $this->leagueid)
                ->update('schedule_game_type', array('default' => 0));
    }

    function delete_gametype($id)
    {
        $this->db->delete('schedule_game_type', array('league_id' => $this->leagueid,
                                                  'id' => $id));
    }

    function delete_title($id)
    {
        $this->db->delete('schedule_title', array('league_id' => $this->leagueid,
                                                    'id' => $id));
    }

    function set_title_text($id, $value)
    {
        $data = array('text' => $value);
        $this->db->where('league_id',$this->leagueid)->where('id',$id);
        $this->db->update('schedule_title', $data);
    }

    function  set_title_display_order($id, $value)
    {
        $data = array('display_order' => $value);
        $this->db->where('league_id',$this->leagueid)->where('id',$id);
        $this->db->update('schedule_title',$data);
    }

    function set_gametype_name($id, $name)
    {
        $data = array('text_id' => $name);
        $this->db->where('league_id',$this->leagueid)->where('id',$id);
        $this->db->update('schedule_game_type',$data);
    }

    function add_title($text)
    {
        $data = array(  'text' => $text, 
                        'league_id' => $this->leagueid);

        $this->db->insert('schedule_title',$data);
    }

}
